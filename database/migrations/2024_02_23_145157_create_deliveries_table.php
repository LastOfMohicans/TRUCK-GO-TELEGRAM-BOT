<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->date('date');
            $table->string('address')->nullable();
            $table->dateTimeTz('datetime')->nullable();
            $table->timeTz('want_time')->nullable();
            $table->timeTz('finish_time')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
