<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя.',
            'name.max' => 'Имя не должно быть длиннее 255 символов.',

            'email.required' => 'Укажите email.',
            'email.email' => 'Введите корректный email.',
            'email.unique' => 'Пользователь с таким email уже существует.',

            'password.required' => 'Укажите пароль.',
            'password.min' => 'Пароль должен быть не короче 6 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ];
    }
}