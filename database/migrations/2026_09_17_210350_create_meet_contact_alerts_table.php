<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meet_contact_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('room_id', 64)->index();
            $table->string('source', 32); // chat | speech | captions
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 32)->nullable(); // tutor | student | unknown
            $table->text('shared_text');
            $table->json('matches'); // [{type, value}, ...]
            $table->string('primary_type', 32)->nullable(); // email | phone | messaging_link
            $table->timestamp('occurred_at')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meet_contact_alerts');
    }
};
