<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutor_class_prep_completions')) {
            return;
        }

        Schema::create('tutor_class_prep_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->string('context_type'); // booking|camp_booking|live_session
            $table->unsignedBigInteger('context_id');
            $table->string('item_key'); // material:lesson-plan | step:objectives | ...
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['tutor_id', 'context_type', 'context_id', 'item_key'], 'tutor_prep_unique');
            $table->index(['context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_class_prep_completions');
    }
};
