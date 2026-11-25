<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN session_type VARCHAR(50) NOT NULL DEFAULT 'enrollment'");
        } else {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('session_type', 50)->default('enrollment')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN session_type ENUM('enrollment', 'live_session') NOT NULL DEFAULT 'enrollment'");
        }
    }
};
