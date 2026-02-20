<?php

namespace SmartDaddy\UserActivity\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SmartDaddy\UserActivity\Models\ModelActivity;

trait TracksUserActivity
{
    public static function bootTracksUserActivity(): void
    {
        static::created(function (Model $model): void {
            if (Auth::check()) {
                try {
                    ModelActivity::firstOrCreate(
                        [
                            'activityable_type' => get_class($model),
                            'activityable_id' => $model->id,
                        ],
                        [
                            'created_by' => Auth::id(),
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to create activity record', [
                        'error' => $e->getMessage(),
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'user_id' => Auth::id(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        static::updated(function (Model $model): void {
            if (Auth::check()) {
                try {
                    ModelActivity::updateOrCreate(
                        [
                            'activityable_type' => get_class($model),
                            'activityable_id' => $model->id,
                        ],
                        [
                            'updated_by' => Auth::id(),
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to update activity record', [
                        'error' => $e->getMessage(),
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'user_id' => Auth::id(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::forceDeleted(function (Model $model): void {
                try {
                    ModelActivity::query()
                        ->where('activityable_type', get_class($model))
                        ->where('activityable_id', $model->id)
                        ->delete();
                } catch (\Exception $e) {
                    Log::error('Failed to delete activity record on force delete', [
                        'error' => $e->getMessage(),
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            });
        } else {
            static::deleted(function (Model $model): void {
                try {
                    ModelActivity::query()
                        ->where('activityable_type', get_class($model))
                        ->where('activityable_id', $model->id)
                        ->delete();
                } catch (\Exception $e) {
                    Log::error('Failed to delete activity record on delete', [
                        'error' => $e->getMessage(),
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            });
        }
    }

    public function activity(): MorphOne
    {
        return $this->morphOne(ModelActivity::class, 'activityable');
    }

    public function creator()
    {
        return $this->activity?->creator;
    }

    public function updater()
    {
        return $this->activity?->updater;
    }
}
