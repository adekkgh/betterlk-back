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
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->dropColumn('link');
        });

        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->json('links')->nullable()->after('student_comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->dropColumn('links');
        });
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->string('link')->nullable()->after('student_comment');
        });
    }
};
