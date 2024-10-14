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
        Schema::create('vendor_vendor_storage', function (Blueprint $table) {
            $table->id();
            $table->uuid('vendor_id')->comment('Идентификатор поставщика.');
            $table->unsignedBigInteger('vendor_storage_id')->comment('Идентификатор склада поставщика.');
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')->on('vendors')
                ->onDelete('cascade');
            $table->foreign('vendor_storage_id')
                ->references('id')->on('vendor_storages')
                ->onDelete('cascade');

            $table->unique(['vendor_id', 'vendor_storage_id'], 'vendor_vendor_storage_unique');
            $table->index('vendor_id');
            $table->index('vendor_storage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_vendor_storage');
    }
};
