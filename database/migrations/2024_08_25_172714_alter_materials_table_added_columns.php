<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('fraction')->nullable()->comment('Фракция. Используется для определения под типа материала.');
            $table->string('type')->nullable()->comment('Тип. Используется для определения под типа материала.');
            $table->unsignedBigInteger('catalog_id')->nullable()->comment('Идентификатор каталога которому принадлежит материал.');


            $table->foreign('catalog_id')->references('id')->on('catalogs');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('fraction');
            $table->dropColumn('type');
            $table->dropForeign('materials_catalog_id_foreign');

            $table->dropColumn('catalog_id');
        });
    }
};
