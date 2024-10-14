<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название категории.');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Родитель категории.');
            $table->timestamps();


            $table->foreign('parent_id')->references('id')->on('catalogs');
        });
    }

    public function down(): void
    {

        Schema::dropIfExists('catalogs');
    }
};
