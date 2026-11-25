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
        Schema::table('camp_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('camp_bookings', 'tutor_id')) {
                $table->foreignId('tutor_id')->nullable()->after('course_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camp_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('camp_bookings', 'tutor_id')) {
                $table->dropForeign(['tutor_id']);
                $table->dropColumn('tutor_id');
            }
        });
    }
};
