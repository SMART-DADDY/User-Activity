<?php

use App\Models\User;
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
    expect($post->fresh()->creator()->id)->toBe($creator->id);
    expect($post->fresh()->updater()->id)->toBe($updater->id);
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
