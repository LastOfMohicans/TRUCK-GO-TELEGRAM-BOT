<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('is_order_search_activated')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropColumns('vendors', [
            'is_order_search_activated'
        ]);
    }
};
