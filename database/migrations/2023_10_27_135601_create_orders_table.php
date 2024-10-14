<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('delivery_id')->nullable();
            $table->boolean('is_activated')->default(false);
            $table->boolean('is_finished')->default(false);
            $table->softDeletes();
            $table->timestamps();


            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('material_id')->references('id')->on('materials');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
