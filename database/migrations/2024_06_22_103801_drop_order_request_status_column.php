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
            $table->dropColumn('status');
        });

        Schema::table('order_request_histories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
