<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['bookings', 'camp_bookings', 'class_schedules'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'meeting_provider')) {
                    $blueprint->string('meeting_provider', 16)->nullable()->after('zoom_start_url');
                }
                if (! Schema::hasColumn($table, 'meeting_room_id')) {
                    $blueprint->string('meeting_room_id', 191)->nullable()->after('meeting_provider');
                }
            });
        }

        if (Schema::hasTable('live_sessions')) {
            Schema::table('live_sessions', function (Blueprint $blueprint) {
                if (! Schema::hasColumn('live_sessions', 'meeting_provider')) {
                    $blueprint->string('meeting_provider', 16)->nullable()->after('start_url');
                }
                if (! Schema::hasColumn('live_sessions', 'meeting_room_id')) {
                    $blueprint->string('meeting_room_id', 191)->nullable()->after('meeting_provider');
                }
            });
        }

        if (Schema::hasTable('class_recordings')) {
            Schema::table('class_recordings', function (Blueprint $blueprint) {
                if (! Schema::hasColumn('class_recordings', 'booking_id')) {
                    $blueprint->unsignedBigInteger('booking_id')->nullable()->after('live_session_id');
                }
                if (! Schema::hasColumn('class_recordings', 'meeting_room_id')) {
                    $blueprint->string('meeting_room_id', 191)->nullable()->after('booking_id');
                }
                if (! Schema::hasColumn('class_recordings', 'provider')) {
                    $blueprint->string('provider', 16)->nullable()->after('meeting_room_id');
                }
                if (! Schema::hasColumn('class_recordings', 'meet_recording_id')) {
                    $blueprint->unsignedBigInteger('meet_recording_id')->nullable()->after('provider');
                }
                if (! Schema::hasColumn('class_recordings', 'storage_path')) {
                    $blueprint->string('storage_path', 500)->nullable()->after('recording_url');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['bookings', 'camp_bookings', 'class_schedules', 'live_sessions'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'meeting_provider')) {
                    $blueprint->dropColumn('meeting_provider');
                }
                if (Schema::hasColumn($table, 'meeting_room_id')) {
                    $blueprint->dropColumn('meeting_room_id');
                }
            });
        }

        if (Schema::hasTable('class_recordings')) {
            Schema::table('class_recordings', function (Blueprint $blueprint) {
                foreach (['booking_id', 'meeting_room_id', 'provider', 'meet_recording_id', 'storage_path'] as $col) {
                    if (Schema::hasColumn('class_recordings', $col)) {
                        $blueprint->dropColumn($col);
                    }
                }
            });
        }
    }
};
