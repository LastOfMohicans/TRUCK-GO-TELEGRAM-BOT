<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('storage_materials', function (Blueprint $table) {
            $table->foreign('material_id')
                ->references('id')->on('materials')
                ->onDelete('cascade');
        });
    }
};
