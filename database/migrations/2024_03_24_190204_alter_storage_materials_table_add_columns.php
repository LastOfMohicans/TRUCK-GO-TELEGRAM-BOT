<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('storage_materials', function (Blueprint $table) {
            $table->integer('cubic_meter_price')->comment('цена за кубометр');
            $table->integer('delivery_cost_per_cubic_meter_per_kilometer')
                ->comment('цена доставки кубометра на 1 километр');
        });
    }

    public function down(): void
    {
        Schema::dropColumns('storage_materials', ['cubic_meter_price', 'delivery_cost_per_cubic_meter_per_kilometer']);
    }
};
