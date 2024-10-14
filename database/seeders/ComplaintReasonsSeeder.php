<?php

namespace Database\Seeders;

use App\Models\ComplaintReason;
use Illuminate\Database\Seeder;

/**
 * Генерируем продовые причины жалоб.
 */
class ComplaintReasonsSeeder extends Seeder
{
    public function run(): void
    {
        ComplaintReason::firstOrCreate([
            'id' => 1,
            'reason' => "ИНН уже есть в системе, но поставщик хочет сменить аккаунт кому принадлежит.",
            'place' => 'Регистрация поставщика.',
        ]);
        ComplaintReason::firstOrCreate([
            'id' => 2,
            'reason' => "ИНН уже есть в системе, но поставщик никогда не работал с нами.",
            'place' => 'Регистрация поставщика.',
        ]);
    }


}
