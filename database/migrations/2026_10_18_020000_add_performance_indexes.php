<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('scheduled_at', 'bookings_scheduled_at_idx');
            $table->index(['student_id', 'scheduled_at'], 'bookings_student_scheduled_idx');
            $table->index('session_type', 'bookings_session_type_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('status', 'payments_status_idx');
            $table->index(['student_id', 'status'], 'payments_student_status_idx');
            $table->index('created_at', 'payments_created_at_idx');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'enrollments_user_status_idx');
            $table->index(['course_id', 'status'], 'enrollments_course_status_idx');
        });

        Schema::table('live_sessions', function (Blueprint $table) {
            $table->index('start_time', 'live_sessions_start_time_idx');
            $table->index(['course_id', 'start_time'], 'live_sessions_course_start_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index(['status', 'is_active'], 'courses_status_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_scheduled_at_idx');
            $table->dropIndex('bookings_student_scheduled_idx');
            $table->dropIndex('bookings_session_type_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_idx');
            $table->dropIndex('payments_student_status_idx');
            $table->dropIndex('payments_created_at_idx');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_user_status_idx');
            $table->dropIndex('enrollments_course_status_idx');
        });

        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropIndex('live_sessions_start_time_idx');
            $table->dropIndex('live_sessions_course_start_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_status_active_idx');
        });
    }
};
