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
            $table->dateTimeTz('delivery_window_start')->comment("Время с которого нужно ожидать доставку.");
            $table->dateTimeTz('delivery_window_end')->comment("Время до которого нужно ожидать доставку.");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_requests', function (Blueprint $table) {
            $table->dropColumn('delivery_window_start');
            $table->dropColumn('delivery_window_end');
        });
    }
};
