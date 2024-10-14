<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TYPE order_request_status_enum ADD VALUE 'cancelled_in_progress';");
    }
};


