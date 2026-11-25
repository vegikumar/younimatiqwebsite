<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_training_modules', function (Blueprint $table) {
            if (! Schema::hasColumn('tutor_training_modules', 'course_name')) {
                $table->string('course_name')->nullable()->after('title');
            }
            if (! Schema::hasColumn('tutor_training_modules', 'class_name')) {
                $table->string('class_name')->nullable()->after('course_name');
            }
            if (! Schema::hasColumn('tutor_training_modules', 'class_number')) {
                $table->string('class_number')->nullable()->after('class_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tutor_training_modules', function (Blueprint $table) {
            foreach (['course_name', 'class_name', 'class_number'] as $col) {
                if (Schema::hasColumn('tutor_training_modules', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
