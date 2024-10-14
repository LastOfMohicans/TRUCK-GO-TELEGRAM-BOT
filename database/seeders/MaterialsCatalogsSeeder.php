<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Material;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Генерируем продовые материалы с каталогами для них.
 */
class MaterialsCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            $this->seedSand();
            $this->seedCrashedStone();
            $this->seedSGM();
            $this->seedConcrete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    const string concreteM200 = "M200";
    const string concreteM250 = "M250";
    const string concreteM300 = "M300";
    const string concreteM350 = "M350";
    const string concreteM400 = "M400";
    const string concreteM450 = "M450";
    const string concreteM500 = "M500";

    const array concreteFractions = [
        self::concreteM200, self::concreteM250, self::concreteM300, self::concreteM350,
        self::concreteM400, self::concreteM450, self::concreteM500,
    ];
    const array concreteTypes = ["Гранитный", "Гравийный"];

    protected function seedConcrete()
    {
        $materialConcrete = Catalog::factory()->create([
            'name' => "Бетон",
            'question' => "Что привезти?",
        ]);
        foreach (self::concreteTypes as $type) {
            $catalogType = Catalog::factory()->create([
                'name' => $type,
                'parent_id' => $materialConcrete->id,
                'question' => "Выберите тип выбранного материала",
            ]);
            foreach (self::concreteFractions as $fraction) {
                $catalogFraction = Catalog::factory()->create([
                    'name' => $fraction,
                    'parent_id' => $catalogType->id,
                    'question' => "Укажите дополнительные параметры",
                ]);

                $fullName = "Бетон {$type} {$fraction}";
                Material::factory()->create(
                    [
                        'name' => 'Бетон',
                        'is_active' => true,
                        'fraction' => $fraction,
                        'type' => $type,
                        'catalog_id' => $catalogFraction->id,
                        'full_name' => $fullName,
                    ]
                );
            }
        }
    }

    const string sgm15 = "До 15% гравия";
    const string sgm20 = "До 20% гравия";
    const string sgm30 = "До 30% гравия";

    protected function seedSGM()
    {
        $materialSGM = Catalog::factory()->create([
            'name' => "ПГС",
            'question' => "Что привезти?",
        ]);

        $catalog15 = Catalog::factory()->create([
            'name' => "До 15% гравия",
            'parent_id' => $materialSGM->id,
            'question' => "Укажите дополнительные параметры",
        ]);

        $catalog20 = Catalog::factory()->create([
            'name' => "До 20% гравия",
            'parent_id' => $materialSGM->id,
            'question' => "Укажите дополнительные параметры",
        ]);

        $catalog30 = Catalog::factory()->create([
            'name' => "До 30% гравия",
            'parent_id' => $materialSGM->id,
            'question' => "Укажите дополнительные параметры",
        ]);


        $fullName = "ПГС " . self::sgm15;
        Material::factory()->create(
            [
                'name' => 'SGM',
                'is_active' => true,
                'fraction' => self::sgm15,
                'catalog_id' => $catalog15->id,
                'full_name' => $fullName,
            ]
        );

        $fullName = "ПГС " . self::sgm20;
        Material::factory()->create(
            [
                'name' => 'SGM',
                'is_active' => true,
                'fraction' => self::sgm20,
                'catalog_id' => $catalog20->id,
                'full_name' => $fullName,
            ]
        );

        $fullName = "ПГС " . self::sgm30;
        Material::factory()->create(
            [
                'name' => 'SGM',
                'is_active' => true,
                'fraction' => self::sgm30,
                'catalog_id' => $catalog30->id,
                'full_name' => $fullName,
            ]
        );

    }

    const string crashedStoneFiveToTwenty = "5-20мм";
    const string crashedStoneTwentyToForty = "20-40мм";
    const string crashedStoneFortyToSeventy = "40-70мм";

    const array crashedStonesFractions = [self::crashedStoneFiveToTwenty, self::crashedStoneTwentyToForty, self::crashedStoneFortyToSeventy];
    const array crashedStonesTypes = ["Гранитный", "Гравийный", "Известняковый"];

    protected function seedCrashedStone(): void
    {
        $materialCrashedStone = Catalog::factory()->create([
            'name' => "Щебень",
            'question' => "Что привезти?",
        ]);
        foreach (self::crashedStonesTypes as $type) {
            $catalogType = Catalog::factory()->create([
                'name' => $type,
                'parent_id' => $materialCrashedStone->id,
                'question' => "Выберите тип выбранного материала",
            ]);
            foreach (self::crashedStonesFractions as $fraction) {
                $catalogFraction = Catalog::factory()->create([
                    'name' => $fraction,
                    'parent_id' => $catalogType->id,
                    'question' => "Укажите дополнительные параметры",
                ]);

                $fullName = "Щебень {$type} {$fraction}";
                Material::factory()->create(
                    [
                        'name' => 'Щебень',
                        'is_active' => true,
                        'fraction' => $fraction,
                        'type' => $type,
                        'catalog_id' => $catalogFraction->id,
                        'full_name' => $fullName,
                    ]
                );
            }
        }
    }

    const string sandFractionZeroFive = "0.5";
    const string sandFractionZeroFiveToTwo = "0.5 до 2мм";
    const string sandFractionTwoToFive = "от 2 до 5мм";

    const array sandFractions = [self::sandFractionZeroFive, self::sandFractionZeroFiveToTwo, self::sandFractionTwoToFive];
    const array sandTypes = ["Карьерный", "Сеянный", "Мытый", "Речной"];

    protected function seedSand()
    {
        $catalogSand = Catalog::factory()->create([
            'name' => "Песок",
            'question' => "Что привезти?",
        ]);
        foreach (self::sandTypes as $type) {
            $catalogType = Catalog::factory()->create([
                'name' => $type,
                'parent_id' => $catalogSand->id,
                'question' => "Выберите тип выбранного материала",
            ]);
            foreach (self::sandFractions as $fraction) {
                $catalogFraction = Catalog::factory()->create([
                    'name' => $fraction,
                    'parent_id' => $catalogType->id,
                    'question' => "Укажите дополнительные параметры",
                ]);

                $fullName = "Песок {$type} {$fraction}";
                Material::factory()->create(
                    [
                        'name' => 'Песок',
                        'is_active' => true,
                        'fraction' => $fraction,
                        'type' => $type,
                        'catalog_id' => $catalogFraction->id,
                        'full_name' => $fullName,
                    ]
                );
            }
        }
    }
}
