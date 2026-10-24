<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))],
            'password' => ['nullable', 'string', 'min:8'],
            'roles'    => ['required', 'array', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique'   => 'Este correo electrónico ya está registrado.',
            'password.min'   => 'La contraseña debe tener al menos 8 caracteres.',
            'roles.required' => 'Debe seleccionar al menos un rol.',
            'roles.exists'   => 'Uno de los roles seleccionados no existe.',
        ];
    }
}