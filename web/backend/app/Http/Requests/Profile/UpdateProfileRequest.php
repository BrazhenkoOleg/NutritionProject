<?php

namespace App\Http\Requests\Profile;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\NutritionGoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'gender' => ['required', 'string', Rule::in(Gender::values())],
            'age' => ['required', 'integer', 'min:14', 'max:100'],
            'height_cm' => ['required', 'numeric', 'min:120', 'max:230'],
            'weight_kg' => ['required', 'numeric', 'min:35', 'max:250'],
            'activity_level' => ['required', 'string', Rule::in(ActivityLevel::values())],
            'goal' => ['required', 'string', Rule::in(NutritionGoal::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'gender.required' => 'Выберите пол.',
            'gender.in' => 'Некорректное значение пола.',

            'age.required' => 'Укажите возраст.',
            'age.integer' => 'Возраст должен быть целым числом.',
            'age.min' => 'Возраст должен быть не меньше 14 лет.',
            'age.max' => 'Возраст должен быть не больше 100 лет.',

            'height_cm.required' => 'Укажите рост.',
            'height_cm.numeric' => 'Рост должен быть числом.',
            'height_cm.min' => 'Рост должен быть не меньше 120 см.',
            'height_cm.max' => 'Рост должен быть не больше 230 см.',

            'weight_kg.required' => 'Укажите вес.',
            'weight_kg.numeric' => 'Вес должен быть числом.',
            'weight_kg.min' => 'Вес должен быть не меньше 35 кг.',
            'weight_kg.max' => 'Вес должен быть не больше 250 кг.',

            'activity_level.required' => 'Выберите уровень активности.',
            'activity_level.in' => 'Некорректный уровень активности.',

            'goal.required' => 'Выберите цель.',
            'goal.in' => 'Некорректная цель.',
        ];
    }
}