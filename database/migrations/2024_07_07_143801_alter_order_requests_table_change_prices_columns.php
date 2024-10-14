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
        Schema::table('order_requests', function (Blueprint $table) {
            $table->dropColumn('cubic_meter_price');
            $table->dropColumn('delivery_cost_per_cubic_meter_per_kilometer');

            $table->integer('material_price')->nullable()->comment('Цена за весь материал.');
            $table->integer('delivery_price')->nullable()->comment('Цена за доставку.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_requests', function (Blueprint $table) {
            $table->integer('cubic_meter_price')->nullable()
                ->comment('Зафиксированная цена за кубометр.');
            $table->integer('delivery_cost_per_cubic_meter_per_kilometer')->nullable()
                ->comment('Зафиксированная цена доставки кубометра на 1 километр.');


            $table->dropColumn('material_price');
            $table->dropColumn('delivery_price');
        });
    }
};
