<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('storage_materials', function (Blueprint $table) {
            $table->foreign('vendor_storage_id')
                ->references('id')->on('vendor_storages')
                ->onDelete('cascade');
        });
    }

    public function down($table): void
    {
        Schema::table('storage_materials', function (Blueprint $table) {
            $table->foreign('vendor_storage_id')
                ->references('id')->on('vendor_storages');
        });
    }
};
