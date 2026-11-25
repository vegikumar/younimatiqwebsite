<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_recordings', function (Blueprint $table) {
            if (! Schema::hasColumn('class_recordings', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('recorded_at')->index();
            }
            if (! Schema::hasColumn('class_recordings', 'deleted_from_storage_at')) {
                $table->timestamp('deleted_from_storage_at')->nullable()->after('expires_at');
            }
        });

        Schema::create('recording_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_recording_id')->constrained('class_recordings')->cascadeOnDelete();
            $table->string('status', 32)->default('pending')->index(); // pending|approved|rejected
            $table->text('note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'class_recording_id']);
        });

        Schema::create('recording_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_recording_id')->constrained('class_recordings')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['student_id', 'class_recording_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recording_assignments');
        Schema::dropIfExists('recording_requests');

        Schema::table('class_recordings', function (Blueprint $table) {
            if (Schema::hasColumn('class_recordings', 'deleted_from_storage_at')) {
                $table->dropColumn('deleted_from_storage_at');
            }
            if (Schema::hasColumn('class_recordings', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
