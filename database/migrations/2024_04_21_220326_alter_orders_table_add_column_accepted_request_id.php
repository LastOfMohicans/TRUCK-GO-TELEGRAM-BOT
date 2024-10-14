<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignUuid('accepted_request_id')->nullable();

            $table->foreign('accepted_request_id')->references('id')->on('order_requests');
        });
    }
};
