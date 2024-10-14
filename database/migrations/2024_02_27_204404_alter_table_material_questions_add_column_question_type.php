<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_type_id')->comment('На какой запрос отвечает данный вопрос');

            $table->foreign('question_type_id')->references('id')->on('question_types');
        });
    }

    public function down(): void
    {
        Schema::table('material_questions', function (Blueprint $table) {
            $table->dropForeign('material_questions_question_type_id_foreign');

            $table->dropColumn('question_type_id');
        });
    }
};
