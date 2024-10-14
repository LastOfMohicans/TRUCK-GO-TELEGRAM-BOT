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
            $table->dateTimeTz('delivery_window_start')->nullable()->change();
            $table->dateTimeTz('delivery_window_end')->nullable()->change();
        });
    }
};
