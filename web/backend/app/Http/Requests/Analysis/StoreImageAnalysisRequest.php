<?php

namespace App\Http\Requests\Analysis;

use App\Enums\MealType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImageAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'meal_type' => ['required', 'string', Rule::in(MealType::values())],
            'entry_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Добавьте фото блюда.',
            'image.image' => 'Файл должен быть изображением.',
            'image.mimes' => 'Поддерживаются только JPG, PNG и WEBP.',
            'image.max' => 'Размер изображения не должен превышать 10 МБ.',
            'meal_type.required' => 'Выберите приём пищи.',
            'meal_type.in' => 'Некорректный тип приёма пищи.',
            'entry_date.required' => 'Укажите дату дневника.',
            'entry_date.date' => 'Некорректная дата дневника.',
        ];
    }
}