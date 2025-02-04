<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_storages', function (Blueprint $table){
            $table->renameColumn('is_activated', 'is_order_search_activated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_storages', function (Blueprint $table){
            $table->renameColumn('is_order_search_activated', 'is_activated');
        });
    }
};
