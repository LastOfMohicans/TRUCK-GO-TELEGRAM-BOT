<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_question_answers', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
        Schema::table('order_question_answers', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });
        Schema::table('order_question_answers', function (Blueprint $table) {
            $table->uuid('order_id');

            $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    public function down(): void
    {

    }
};
