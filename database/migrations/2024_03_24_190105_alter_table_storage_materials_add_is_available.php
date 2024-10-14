<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('storage_materials', function (Blueprint $table) {
            $table->boolean('is_available')->default(false)->comment('в наличие ли товар');
        });
    }

    public function down(): void
    {
        Schema::dropColumns('storage_materials', [
            'is_available'
        ]);
    }
};
