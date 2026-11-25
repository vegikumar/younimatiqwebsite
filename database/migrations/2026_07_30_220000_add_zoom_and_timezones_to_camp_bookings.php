<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camp_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('camp_bookings', 'zoom_join_url')) {
                $table->text('zoom_join_url')->nullable();
            }
            if (!Schema::hasColumn('camp_bookings', 'zoom_start_url')) {
                $table->text('zoom_start_url')->nullable();
            }
            if (!Schema::hasColumn('camp_bookings', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable();
            }
            if (!Schema::hasColumn('camp_bookings', 'parent_timezone')) {
                $table->string('parent_timezone')->nullable();
            }
            if (!Schema::hasColumn('camp_bookings', 'tutor_timezone')) {
                $table->string('tutor_timezone')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('camp_bookings', function (Blueprint $table) {
            $table->dropColumn(['zoom_join_url', 'zoom_start_url', 'scheduled_at', 'parent_timezone', 'tutor_timezone']);
        });
    }
};
