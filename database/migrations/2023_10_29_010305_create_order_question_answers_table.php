<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id');
            $table->foreignId('material_question_id');
            $table->foreignId('material_question_answer_id')->nullable();
            $table->string('answer')->nullable();
            $table->softDeletes();
            $table->timestamps();


            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('material_question_id')->references('id')->on('material_questions');
            $table->foreign('material_question_answer_id')->references('id')->on('material_question_answers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_question_answers');
    }
};
