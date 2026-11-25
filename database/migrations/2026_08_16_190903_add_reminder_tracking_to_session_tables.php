<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['bookings', 'camp_bookings', 'class_schedules'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('reminder_24h_sent')->default(false)->after('scheduled_at');
                $table->boolean('reminder_1h_sent')->default(false)->after('reminder_24h_sent');
                $table->boolean('reminder_30m_sent')->default(false)->after('reminder_1h_sent');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_tables', function (Blueprint $table) {
            //
        });
    }
};
