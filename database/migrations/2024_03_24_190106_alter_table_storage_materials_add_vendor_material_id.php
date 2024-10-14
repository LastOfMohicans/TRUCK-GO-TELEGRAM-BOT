<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('storage_materials', function (Blueprint $table) {
            $table->string('vendor_material_id')->nullable()
                ->comment('айди товара в системе поставщика');
        });
    }

    public function down(): void
    {
        Schema::dropColumns('storage_materials', [
            'vendor_material_id'
        ]);
    }
};
