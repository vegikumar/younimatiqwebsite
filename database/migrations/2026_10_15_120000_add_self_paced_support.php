<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'self_paced_enabled')) {
                $table->boolean('self_paced_enabled')->default(false)->after('course_type');
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'delivery_mode')) {
                $table->string('delivery_mode')->nullable()->after('pricing_model');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'pricing_model')) {
                $table->string('pricing_model')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'delivery_mode')) {
                $table->string('delivery_mode')->nullable()->after('pricing_model');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'self_paced_enabled')) {
                $table->dropColumn('self_paced_enabled');
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'delivery_mode')) {
                $table->dropColumn('delivery_mode');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            foreach (['pricing_model', 'delivery_mode'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
