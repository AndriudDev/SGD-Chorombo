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
            'folio'        => ['required', 'string', 'max:50'],
            'tipo'         => ['required', 'in:memo,oficio'],
            'fecha'        => ['required', 'date'],
            'destinatario' => ['required', 'string', 'max:255'],
            'asunto'       => ['required', 'string'],
            'archivo'      => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'folio.required'       => 'El folio es obligatorio.',
            'tipo.required'        => 'El tipo de documento es obligatorio.',
            'tipo.in'              => 'El tipo debe ser memo u oficio.',
            'fecha.required'       => 'La fecha es obligatoria.',
            'destinatario.required' => 'El destinatario es obligatorio.',
            'asunto.required'      => 'El asunto es obligatorio.',
            'archivo.file'         => 'Debe adjuntar un archivo válido.',
            'archivo.mimes'        => 'Solo se permiten archivos PDF o Word.',
            'archivo.max'          => 'El archivo no debe superar los 15 MB.',
        ];
    }
}