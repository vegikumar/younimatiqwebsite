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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('zoom_passcode')->nullable()->after('zoom_join_url');
        });
        
        Schema::table('camp_bookings', function (Blueprint $table) {
            $table->string('zoom_passcode')->nullable()->after('zoom_join_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('zoom_passcode');
        });
        
        Schema::table('camp_bookings', function (Blueprint $table) {
            $table->dropColumn('zoom_passcode');
        });
    }
};
