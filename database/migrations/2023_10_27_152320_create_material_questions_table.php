<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('material_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->text('question');
            $table->enum('question_type', ['select', 'user_enter_int', 'user_enter_string']);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();


            $table->foreign('material_id')->references('id')->on('materials');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_questions');
    }
};
