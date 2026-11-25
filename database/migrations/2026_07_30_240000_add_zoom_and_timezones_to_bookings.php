<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'zoom_start_url')) {
                $table->text('zoom_start_url')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'parent_timezone')) {
                $table->string('parent_timezone')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'tutor_timezone')) {
                $table->string('tutor_timezone')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['zoom_start_url', 'parent_timezone', 'tutor_timezone']);
        });
    }
};
