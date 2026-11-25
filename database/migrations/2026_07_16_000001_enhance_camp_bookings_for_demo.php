<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camp_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('camp_bookings', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('id')->constrained('courses')->nullOnDelete();
            }
            if (! Schema::hasColumn('camp_bookings', 'country')) {
                $table->string('country')->nullable()->after('state');
            }
            if (! Schema::hasColumn('camp_bookings', 'currency')) {
                $table->string('currency', 3)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('camp_bookings', 'razorpay_order_id')) {
                $table->string('razorpay_order_id')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('camp_bookings', 'razorpay_payment_id')) {
                $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('camp_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('camp_bookings', 'course_id')) {
                $table->dropConstrainedForeignId('course_id');
            }
            foreach (['country', 'currency', 'razorpay_order_id', 'razorpay_payment_id'] as $col) {
                if (Schema::hasColumn('camp_bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
