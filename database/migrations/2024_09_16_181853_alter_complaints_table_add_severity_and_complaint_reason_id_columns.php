<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE TYPE complaint_severity_enum AS ENUM ('low', 'medium', 'high', 'critical');");

        Schema::table('complaints', function (Blueprint $table) {
            $table->integer('complaint_reason_id')->comment('Идентификатор причины жалобы.');

            $table->foreign('complaint_reason_id')
                ->references('id')->on('complaint_reasons');
        });

        DB::statement(
            "ALTER TABLE complaints
ADD COLUMN IF NOT EXISTS severity complaint_severity_enum NOT NULL;"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('severity');
            $table->dropColumn('complaint_reason_id');
        });
    }
};
