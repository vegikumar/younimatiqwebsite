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
        Schema::create('camp_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('parent_name');
            $table->string('child_name');
            $table->string('camp_name');
            $table->string('email');
            $table->string('phone');
            $table->string('age');
            $table->string('state')->nullable();
            $table->string('payment_status')->default('pending');
            $table->decimal('amount', 8, 2)->default(1.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camp_bookings');
    }
};
