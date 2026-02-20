<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_activities')) {
            return;
        }

        Schema::create('model_activities', function (Blueprint $table): void {
            $table->id();
            $table->morphs('activityable');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['activityable_type', 'activityable_id'], 'model_activities_activityable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_activities');
    }
};
