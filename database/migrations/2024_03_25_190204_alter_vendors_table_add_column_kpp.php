<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('kpp')->nullable()->comment('идентификатор, который присваивается компании налоговым органом');
        });
    }

    public function down($table): void
    {
        Schema::dropColumns($table, 'kpp');
    }
};
