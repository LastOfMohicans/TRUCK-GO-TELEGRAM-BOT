<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE orders
ADD COLUMN IF NOT EXISTS status order_status_enum;");

        DB::statement("ALTER TABLE orders ALTER COLUMN status SET NOT NULL;");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders
DROP COLUMN IF EXISTS status order_status_enum;");
    }
};


