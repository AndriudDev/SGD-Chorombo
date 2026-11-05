<?php

namespace App\Http\Requests\RRHH;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermisoAdminRequest extends FormRequest
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
            'archivo' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:15360'],
        ];

        // Dirección, Administración e Inspectoría pueden cambiar el funcionario al editar
        if ($this->user()?->hasAnyRole(['directora', 'administracion', 'inspectoria'])) {
            $rules['user_id'] = ['sometimes', 'integer', 'exists:users,id'];
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
            'archivo.file' => 'Debe adjuntar un respaldo válido.',
            'archivo.mimes' => 'Solo se permiten archivos PDF, Word o imágenes (JPG, PNG, WEBP).',
            'archivo.max' => 'El archivo no debe superar los 15 MB.',
        ];

        if ($this->user()?->hasAnyRole(['directora', 'administracion', 'inspectoria'])) {
            $messages['user_id.exists'] = 'El funcionario seleccionado no existe.';
        }

        return $messages;
    }
}
