<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('question_types', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique()->comment('Вопрос на который отвечает вопрос');
            $table->text('description');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_types');
    }
};
