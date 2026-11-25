<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_session_recaps', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_session_recaps', 'red_flag')) {
                $table->boolean('red_flag')->default(false);
            }
            if (!Schema::hasColumn('ai_session_recaps', 'detected_pii')) {
                $table->text('detected_pii')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_session_recaps', function (Blueprint $table) {
            $table->dropColumn(['red_flag', 'detected_pii']);
        });
    }
};
