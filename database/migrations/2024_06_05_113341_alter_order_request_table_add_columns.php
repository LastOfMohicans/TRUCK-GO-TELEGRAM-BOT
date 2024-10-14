<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_requests', function (Blueprint $table) {
            $table->float('distance')
                ->comment('Расстояние между складом и точкой доставки напрямую в км.');
            $table->timestamp('archived_at')->nullable()
                ->comment('Время когда заказ перестал быть активным. То есть на него нельзя откликнуться или отказаться.');
            $table->integer('cubic_meter_price')->nullable()
                ->comment('Зафиксированная цена за кубометр.');
            $table->integer('delivery_cost_per_cubic_meter_per_kilometer')->nullable()
                ->comment('Зафиксированная цена доставки кубометра на 1 километр.');
        });
    }

    public function down(): void
    {
        Schema::table('order_requests', function (Blueprint $table) {
            $table->dropColumn('distance');
            $table->dropColumn('archived_at');
            $table->dropColumn('cubic_meter_price');
            $table->dropColumn('delivery_cost_per_cubic_meter_per_kilometer');
        });
    }
};
