<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('material_question_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_question_id');
            $table->string('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();


            $table->foreign('material_question_id')->references('id')->on('material_questions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_question_answers');
    }
};
