<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("CREATE TYPE order_request_status_enum AS ENUM ('waiting_response', 'price_negotiation', 'price_negotiation_denied',
        'in_progress', 'waiting_documents', 'request_denied', 'completed');");
    }

    public function down(): void
    {
        DB::statement("DROP TYPE order_request_status_enum;");
    }
};


