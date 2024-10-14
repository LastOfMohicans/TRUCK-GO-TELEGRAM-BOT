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
        Schema::create('complaint_reasons', function (Blueprint $table) {
            $table->id();
            $table->text('reason')->comment('Почему произошла проблема.');
            $table->text('place')->comment('Где произошла проблема.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_reasons');
    }
};
