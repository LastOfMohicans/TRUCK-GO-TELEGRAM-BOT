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
            $table->float('discount')->nullable()->comment('Процент скидки на исполнения заказа.');
            $table->boolean('is_discounted')->nullable()->comment('Показывает предоставил ли поставщик скидку при запросе клиента.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_requests', function (Blueprint $table) {
            $table->dropColumn('discount');
            $table->dropColumn('is_discounted');
        });
    }
};
