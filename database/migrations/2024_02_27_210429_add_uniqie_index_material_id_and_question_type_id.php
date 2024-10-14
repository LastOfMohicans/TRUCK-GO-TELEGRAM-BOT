<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_questions', function (Blueprint $table) {
            $table->unique(['material_id', 'question_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('material_questions', function (Blueprint $table) {
            $table->dropUnique(['material_id', 'question_type_id']);
        });
    }
};
