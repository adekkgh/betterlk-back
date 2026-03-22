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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['name' => 'admin',     'display_name' => 'Администратор', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'moderator', 'display_name' => 'Модератор',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'professor', 'display_name' => 'Преподаватель', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'student',   'display_name' => 'Студент',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
