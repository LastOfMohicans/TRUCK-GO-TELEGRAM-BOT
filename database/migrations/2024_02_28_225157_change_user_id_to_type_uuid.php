<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_user_id_foreign');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
