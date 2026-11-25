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
        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('price_usd', 8, 2)->default(12.00)->after('per_class_price');
            $table->decimal('price_aed', 8, 2)->default(30.00)->after('price_usd');
            $table->decimal('price_inr', 8, 2)->default(600.00)->after('price_aed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['price_usd', 'price_aed', 'price_inr']);
        });
    }
};
