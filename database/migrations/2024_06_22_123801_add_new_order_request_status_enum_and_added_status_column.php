<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP TYPE order_request_status_enum;");

        DB::statement("CREATE TYPE order_request_status_enum AS ENUM ('created', 'waiting_client_response', 'client_want_discount',
        'in_progress', 'waiting_documents', 'completed', 'cancelled');");

        DB::statement("ALTER TABLE order_requests
ADD COLUMN IF NOT EXISTS status order_request_status_enum NOT NULL;");

        DB::statement("ALTER TABLE order_request_histories
ADD COLUMN IF NOT EXISTS status order_request_status_enum NOT NULL;");
    }
};
