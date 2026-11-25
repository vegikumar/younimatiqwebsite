<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_automations', function (Blueprint $table) {
            if (! Schema::hasColumn('email_automations', 'exclude_emails')) {
                $table->longText('exclude_emails')->nullable()->after('audiences');
            }
            if (! Schema::hasColumn('email_automations', 'exclude_camps')) {
                $table->boolean('exclude_camps')->default(true)->after('max_courses');
            }
            if (! Schema::hasColumn('email_automations', 'skip_recent_days')) {
                $table->unsignedTinyInteger('skip_recent_days')->default(2)->after('exclude_camps');
            }
            if (! Schema::hasColumn('email_automations', 'utm_campaign')) {
                $table->string('utm_campaign')->nullable()->after('skip_recent_days');
            }
            if (! Schema::hasColumn('email_automations', 'notify_admin_on_send')) {
                $table->boolean('notify_admin_on_send')->default(true)->after('utm_campaign');
            }
        });

        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason')->default('unsubscribe');
            $table->string('source')->nullable();
            $table->timestamp('suppressed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');

        Schema::table('email_automations', function (Blueprint $table) {
            foreach (['exclude_emails', 'exclude_camps', 'skip_recent_days', 'utm_campaign', 'notify_admin_on_send'] as $col) {
                if (Schema::hasColumn('email_automations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
