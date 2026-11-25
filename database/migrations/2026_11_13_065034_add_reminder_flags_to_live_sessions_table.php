<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('live_sessions', 'reminder_24h_sent')) {
                $table->boolean('reminder_24h_sent')->default(false)->after('status');
            }
            if (! Schema::hasColumn('live_sessions', 'reminder_1h_sent')) {
                $table->boolean('reminder_1h_sent')->default(false)->after('reminder_24h_sent');
            }
            if (! Schema::hasColumn('live_sessions', 'reminder_30m_sent')) {
                $table->boolean('reminder_30m_sent')->default(false)->after('reminder_1h_sent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            foreach (['reminder_24h_sent', 'reminder_1h_sent', 'reminder_30m_sent'] as $col) {
                if (Schema::hasColumn('live_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
