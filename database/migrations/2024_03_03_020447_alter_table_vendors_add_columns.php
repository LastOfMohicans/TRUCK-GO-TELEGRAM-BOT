<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('company_name');
            $table->string('ogrn');
            $table->string('address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropColumns('vendors', [
            'company_name', 'ogrn', 'address'
        ]);
    }
};
