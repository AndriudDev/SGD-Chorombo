<?php

namespace App\Http\Requests\UTP;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompromisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:500'],
            'fecha_limite' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripción del compromiso es obligatoria.',
            'fecha_limite.required' => 'La fecha límite de entrega es obligatoria.',
        ];
    }
}
