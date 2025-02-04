<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'is_active' => true,
            'created_at' => now(),
        ];
    }
}
