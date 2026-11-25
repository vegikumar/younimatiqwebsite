<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_availabilities', function (Blueprint $table) {
            if (!Schema::hasColumn('tutor_availabilities', 'format_type')) {
                $table->string('format_type')->default('tutoring_1on1')->after('course_id');
            }
            if (!Schema::hasColumn('tutor_availabilities', 'timezone')) {
                $table->string('timezone')->default('UTC')->after('is_booked');
            }
            if (!Schema::hasColumn('tutor_availabilities', 'max_capacity')) {
                $table->integer('max_capacity')->default(1)->after('timezone');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('UTC')->after('role');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'timezone')) {
                $table->string('timezone')->default('UTC')->after('scheduled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });

        Schema::table('tutor_availabilities', function (Blueprint $table) {
            if (Schema::hasColumn('tutor_availabilities', 'format_type')) {
                $table->dropColumn('format_type');
            }
            if (Schema::hasColumn('tutor_availabilities', 'timezone')) {
                $table->dropColumn('timezone');
            }
            if (Schema::hasColumn('tutor_availabilities', 'max_capacity')) {
                $table->dropColumn('max_capacity');
            }
        });
    }
};
