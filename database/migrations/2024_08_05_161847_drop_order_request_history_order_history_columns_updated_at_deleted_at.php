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
        Schema::table('order_histories', function (Blueprint $table) {
            $table->dropColumn('updated_at');
            $table->dropColumn('deleted_at');
        });

        Schema::table('order_request_histories', function (Blueprint $table) {
            $table->dropColumn('updated_at');
            $table->dropColumn('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->timestamp('updated_at')->useCurrent();
            $table->softDeletes();
        });

        Schema::table('order_request_histories', function (Blueprint $table) {
            $table->timestamp('updated_at')->useCurrent();
            $table->softDeletes();
        });
    }
};
