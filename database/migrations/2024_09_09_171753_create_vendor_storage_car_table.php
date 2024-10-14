<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_storage_car', function (Blueprint $table) {
            $table->id()->comment('Уникальный идентификатор машины.');
            $table->string('car_number', 256)->comment('Номер машины.');
            $table->unsignedBigInteger('vendor_storage_id')->comment('Идентификатор склада поставщика.');

            $table->foreign('vendor_storage_id')
                ->references('id')->on('vendor_storages')
                ->onDelete('cascade');

            $table->string('driver_telegram_chat_id')->nullable()->comment('Идентификатор чата водителя в Telegram.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_storage_car');
    }
};
