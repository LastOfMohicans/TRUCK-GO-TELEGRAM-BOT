<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id');
            $table->foreignUuid('vendor_id');
            $table->softDeletes();
            $table->timestamps();


            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('vendor_id')->references('id')->on('vendors');
        });

        DB::statement("ALTER TABLE order_requests ADD COLUMN IF NOT EXISTS status order_request_status_enum;");

        DB::statement("ALTER TABLE orders ALTER COLUMN status SET NOT NULL;");
    }

    public function down(): void
    {
        Schema::dropIfExists('order_requests');
    }
};
