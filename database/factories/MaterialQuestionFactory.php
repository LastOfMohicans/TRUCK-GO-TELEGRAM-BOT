<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\MaterialQuestion;
use App\Models\QuestionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialQuestion>
 */
class MaterialQuestionFactory extends Factory
{
    protected $model = MaterialQuestion::class;

    public function definition()
    {
        return [
            'material_id' => Material::factory(),
            'question' => $this->faker->sentence,
            'question_answer_type' => $this->faker->randomElement(['select', 'user_enter_int', 'user_enter_string']),
            'is_active' => true,
            'order' => $this->faker->randomDigitNotNull,
            'updated_at' => now(),
            'created_at' => now(),
            'required' => $this->faker->boolean,
            'question_type_id' => QuestionType::factory(),
        ];
    }
}
