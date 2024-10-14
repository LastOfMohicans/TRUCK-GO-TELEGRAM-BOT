<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id');
            $table->string('changed_by')->comment('Показывает того кто сделал изменения');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders');
        });

        DB::statement("ALTER TABLE order_histories
ADD COLUMN IF NOT EXISTS status order_status_enum;");
    }

    public function down(): void
    {
        Schema::dropIfExists('order_histories');
    }
};
