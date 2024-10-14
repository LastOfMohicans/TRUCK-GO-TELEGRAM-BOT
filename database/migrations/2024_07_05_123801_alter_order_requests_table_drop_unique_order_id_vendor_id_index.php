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
            $table->dropUnique('order_requests_order_id_vendor_id_unique');

            $table->unique(['order_id', 'vendor_id', 'vendor_storage_id']);
        });
    }
};
