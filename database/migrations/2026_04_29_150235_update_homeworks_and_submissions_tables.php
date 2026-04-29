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
        // Убираем type из homeworks
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Добавляем комментарий студента в submissions
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->text('student_comment')->nullable()->after('student_id');
            $table->string('link')->nullable()->after('student_comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->enum('type', ['written', 'oral', 'file', 'link'])->default('file');
        });

        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->dropColumn(['student_comment', 'link']);
        });
    }
};
