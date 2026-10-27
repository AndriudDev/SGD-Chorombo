<?php

namespace App\Http\Requests\RRHH;

use Illuminate\Foundation\Http\FormRequest;

class StorePermisoAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'tipo_jornada' => ['required', 'in:completa,media'],
            'motivo' => ['required', 'string'],
        ];

        // Admin/Directora pueden crear permisos para otro usuario
        if ($this->user()?->hasAnyRole(['directora', 'administracion'])) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de término es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de término debe ser igual o posterior a la de inicio.',
            'tipo_jornada.required' => 'El tipo de jornada es obligatorio.',
            'tipo_jornada.in' => 'La jornada debe ser completa o media.',
            'motivo.required' => 'El motivo es obligatorio.',
        ];

        if ($this->user()?->hasAnyRole(['directora', 'administracion'])) {
            $messages['user_id.required'] = 'Debe seleccionar el funcionario solicitante.';
            $messages['user_id.exists'] = 'El funcionario seleccionado no existe.';
        }

        return $messages;
    }
}
