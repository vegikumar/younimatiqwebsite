<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_feedback', function (Blueprint $table) {
            $table->timestamp('shared_with_parent_at')->nullable()->after('admin_notes');
            $table->text('parent_message')->nullable()->after('shared_with_parent_at');
            $table->unsignedBigInteger('shared_by')->nullable()->after('parent_message');
        });
    }

    public function down(): void
    {
        Schema::table('class_feedback', function (Blueprint $table) {
            $table->dropColumn(['shared_with_parent_at', 'parent_message', 'shared_by']);
        });
    }
};
