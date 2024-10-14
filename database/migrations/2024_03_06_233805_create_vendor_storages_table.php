<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_storages', function (Blueprint $table) {
            $table->id();
            $table->string('latitude');
            $table->string('longitude');
            $table->boolean('is_activated')->default(false);
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('address')->nullable();
            $table->foreignUuid('vendor_id');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_storages');
    }
};
