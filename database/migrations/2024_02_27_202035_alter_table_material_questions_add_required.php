<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_questions', function (Blueprint $table) {
            $table->boolean('required')->comment('Обязательный ли для ответа вопрос.');
        });
    }

    public function down(): void
    {
        Schema::table('material_questions', function (Blueprint $table) {
            $table->dropColumn('required');
        });
    }
};
