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
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending'");
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('info');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending'");
        }
    }
};
