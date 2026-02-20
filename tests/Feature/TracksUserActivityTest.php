<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use SmartDaddy\UserActivity\Models\ModelActivity;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\SoftPost;

test('it creates activity record on create for authenticated user', function (): void {
    $user = User::query()->create(['name' => 'Creator']);
    $this->be($user);

    $post = Post::query()->create(['title' => 'First']);

    $activity = ModelActivity::query()
        ->where('activityable_type', Post::class)
        ->where('activityable_id', $post->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->created_by)->toBe($user->id);
});

test('it does not create activity record without authenticated user', function (): void {
    $post = Post::query()->create(['title' => 'First']);

    $activity = ModelActivity::query()
        ->where('activityable_type', Post::class)
        ->where('activityable_id', $post->id)
        ->first();

    expect($activity)->toBeNull();
});

test('it updates updated_by for authenticated user', function (): void {
    $creator = User::query()->create(['name' => 'Creator']);
    $updater = User::query()->create(['name' => 'Updater']);

    $this->be($creator);
    $post = Post::query()->create(['title' => 'First']);

    $this->be($updater);
    $post->update(['title' => 'Updated']);

    $activity = ModelActivity::query()
        ->where('activityable_type', Post::class)
        ->where('activityable_id', $post->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->created_by)->toBe($creator->id);
    expect($activity->updated_by)->toBe($updater->id);
    expect(ModelActivity::query()->count())->toBe(1);
    expect($post->fresh()->creator()->id)->toBe($creator->id);
    expect($post->fresh()->updater()->id)->toBe($updater->id);
});

test('it does not set updated_by when no user is authenticated on update', function (): void {
    $creator = User::query()->create(['name' => 'Creator']);
    $this->be($creator);
    $post = Post::query()->create(['title' => 'First']);

    $this->app['auth']->guard()->logout();
    $post->update(['title' => 'Updated']);

    $activity = ModelActivity::query()
        ->where('activityable_type', Post::class)
        ->where('activityable_id', $post->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->created_by)->toBe($creator->id);
    expect($activity->updated_by)->toBeNull();
});

test('it creates activity record from update when row does not exist yet', function (): void {
    $post = Post::query()->create(['title' => 'First']);
    $updater = User::query()->create(['name' => 'Updater']);

    $this->be($updater);
    $post->update(['title' => 'Updated']);

    $activity = ModelActivity::query()
        ->where('activityable_type', Post::class)
        ->where('activityable_id', $post->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->created_by)->toBeNull();
    expect($activity->updated_by)->toBe($updater->id);
});

test('it deletes activity record when model is deleted', function (): void {
    $user = User::query()->create(['name' => 'Creator']);
    $this->be($user);

    $post = Post::query()->create(['title' => 'First']);
    $post->delete();

    $this->assertDatabaseMissing('model_activities', [
        'activityable_type' => Post::class,
        'activityable_id' => $post->id,
    ]);
});

test('it deletes activity record only on force delete for soft deleted models', function (): void {
    $user = User::query()->create(['name' => 'Creator']);
    $this->be($user);

    $post = SoftPost::query()->create(['title' => 'First']);
    $post->delete();

    $this->assertDatabaseHas('model_activities', [
        'activityable_type' => SoftPost::class,
        'activityable_id' => $post->id,
    ]);

    $post->forceDelete();

    $this->assertDatabaseMissing('model_activities', [
        'activityable_type' => SoftPost::class,
        'activityable_id' => $post->id,
    ]);
});

test('it returns null relations when activity does not exist', function (): void {
    $post = Post::query()->create(['title' => 'First']);

    expect($post->activity)->toBeNull();
    expect($post->creator())->toBeNull();
    expect($post->updater())->toBeNull();
});

test('model activity relations resolve morph and users', function (): void {
    $creator = User::query()->create(['name' => 'Creator']);
    $updater = User::query()->create(['name' => 'Updater']);

    $this->be($creator);
    $post = Post::query()->create(['title' => 'First']);
    $this->be($updater);
    $post->update(['title' => 'Updated']);

    $activity = ModelActivity::query()->firstOrFail();

    expect($activity->activityable)->not->toBeNull();
    expect($activity->activityable->id)->toBe($post->id);
    expect($activity->creator)->not->toBeNull();
    expect($activity->creator->id)->toBe($creator->id);
    expect($activity->updater)->not->toBeNull();
    expect($activity->updater->id)->toBe($updater->id);
});

test('it logs and continues when creating activity fails', function (): void {
    Log::spy();
    $user = User::query()->create(['name' => 'Creator']);
    $this->be($user);
    Schema::dropIfExists('model_activities');

    Post::query()->create(['title' => 'First']);

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Failed to create activity record'
            && ($context['model_type'] ?? null) === Post::class
            && ($context['user_id'] ?? null) === $user->id
            && isset($context['error'])
            && isset($context['trace']));
});

test('it logs and continues when updating activity fails', function (): void {
    Log::spy();
    $user = User::query()->create(['name' => 'Updater']);
    $post = Post::query()->create(['title' => 'First']);
    $this->be($user);
    Schema::dropIfExists('model_activities');

    $post->update(['title' => 'Updated']);

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Failed to update activity record'
            && ($context['model_type'] ?? null) === Post::class
            && ($context['user_id'] ?? null) === $user->id
            && isset($context['error'])
            && isset($context['trace']));
});

test('it logs and continues when deleting activity fails for normal deletes', function (): void {
    Log::spy();
    $user = User::query()->create(['name' => 'Creator']);
    $this->be($user);
    $post = Post::query()->create(['title' => 'First']);
    Schema::dropIfExists('model_activities');

    $post->delete();

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Failed to delete activity record on delete'
            && ($context['model_type'] ?? null) === Post::class
            && isset($context['error'])
            && isset($context['trace']));
});

test('it logs and continues when deleting activity fails for force deletes', function (): void {
    Log::spy();
    $user = User::query()->create(['name' => 'Creator']);
    $this->be($user);
    $post = SoftPost::query()->create(['title' => 'First']);
    Schema::dropIfExists('model_activities');

    $post->forceDelete();

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Failed to delete activity record on force delete'
            && ($context['model_type'] ?? null) === SoftPost::class
            && isset($context['error'])
            && isset($context['trace']));
});
