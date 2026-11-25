<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('per_class_price', 8, 2)->nullable()->after('price');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->enum('pricing_model', ['full', 'per_class'])->default('full')->after('status');
            $table->integer('paid_classes_count')->default(0)->after('pricing_model');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('is_paid')->default(true)->after('status');
            $table->boolean('reminder_sent')->default(false)->after('is_paid');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('per_class_price');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['pricing_model', 'paid_classes_count']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'reminder_sent']);
        });
    }
};
