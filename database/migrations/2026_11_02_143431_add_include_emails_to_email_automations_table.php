<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_automations', function (Blueprint $table) {
            if (! Schema::hasColumn('email_automations', 'include_emails')) {
                $table->longText('include_emails')->nullable()->after('exclude_emails');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_automations', function (Blueprint $table) {
            if (Schema::hasColumn('email_automations', 'include_emails')) {
                $table->dropColumn('include_emails');
            }
        });
    }
};
