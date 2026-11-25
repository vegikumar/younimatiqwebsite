<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_link_tutor')) {
            return;
        }

        Schema::create('activity_link_tutor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_link_id')->constrained('activity_links')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['activity_link_id', 'tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_link_tutor');
    }
};
