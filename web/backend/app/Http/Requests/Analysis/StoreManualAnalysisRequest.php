<?php

namespace App\Http\Requests\Analysis;

use App\Enums\MealType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'meal_type' => ['required', 'string', Rule::in(MealType::values())],
            'entry_date' => ['required', 'date'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.class_name' => ['required', 'string'],
            'products.*.weight_g' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'meal_type.required' => 'Выберите приём пищи.',
            'meal_type.in' => 'Некорректный тип приёма пищи.',
            'entry_date.required' => 'Укажите дату дневника.',
            'entry_date.date' => 'Некорректная дата дневника.',
            'products.required' => 'Добавьте хотя бы один продукт.',
            'products.min' => 'Добавьте хотя бы один продукт.',
            'products.*.class_name.required' => 'Выберите продукт.',
            'products.*.weight_g.required' => 'Укажите массу продукта.',
            'products.*.weight_g.numeric' => 'Масса продукта должна быть числом.',
            'products.*.weight_g.min' => 'Масса продукта должна быть больше 0.',
        ];
    }
}