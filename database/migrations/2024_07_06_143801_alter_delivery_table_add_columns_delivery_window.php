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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dateTimeTz('wanted_delivery_window_start')->comment("Время с которого клиент ожидает доставку.");
            $table->dateTimeTz('wanted_delivery_window_end')->comment("Время до которого клиент ожидает доставку.");

            $table->dropColumn('want_time');
            $table->dropColumn('deleted_at');
            $table->dropColumn('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('wanted_delivery_window_start');
            $table->dropColumn('wanted_delivery_window_end');

            $table->date('date');
            $table->timeTz('want_time')->nullable();
            $table->softDeletes();
        });
    }
};
