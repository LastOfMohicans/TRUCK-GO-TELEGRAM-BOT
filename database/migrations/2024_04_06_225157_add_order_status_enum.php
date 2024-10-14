<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("CREATE TYPE order_status_enum AS ENUM ('created', 'vendor_search', 'waiting_commission_payment',
        'creating_documents', 'loading', 'on_the_way',
         'waiting_to_receive', 'waiting_full_payment', 'cancelled', 'completed');");
    }

    public function down(): void
    {
        DB::statement("DROP TYPE order_status_enum;");
    }
};


