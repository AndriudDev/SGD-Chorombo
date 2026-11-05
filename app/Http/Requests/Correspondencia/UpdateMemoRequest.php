<?php

namespace App\Http\Requests\Correspondencia;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:memo,oficio'],
            'fecha' => ['required', 'date'],
            'destinatario' => ['required', 'string', 'max:255'],
            'asunto' => ['required', 'string'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de documento es obligatorio.',
            'tipo.in' => 'El tipo debe ser memo u oficio.',
            'fecha.required' => 'La fecha es obligatoria.',
            'destinatario.required' => 'El destinatario es obligatorio.',
            'asunto.required' => 'El asunto es obligatorio.',
            'archivo.file' => 'Debe adjuntar un archivo válido.',
            'archivo.mimes' => 'Solo se permiten archivos PDF, Word o imágenes (JPG, PNG, WEBP).',
            'archivo.max' => 'El archivo no debe superar los 15 MB.',
        ];
    }
}
