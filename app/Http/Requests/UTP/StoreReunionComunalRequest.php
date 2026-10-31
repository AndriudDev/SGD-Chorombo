<?php

namespace App\Http\Requests\UTP;

use App\Models\ReunionComunal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReunionComunalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'categoria' => ['required', Rule::in(array_keys(ReunionComunal::CATEGORIAS))],
            'fecha' => ['required', 'date'],
            'descripcion' => ['nullable', 'string'],
            'archivo' => ['required', 'file', 'mimes:pdf,ppt,pptx,doc,docx', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título de la reunión es obligatorio.',
            'categoria.required' => 'La categoría es obligatoria.',
            'categoria.in' => 'La categoría indicada no es válida.',
            'fecha.required' => 'La fecha de la reunión es obligatoria.',
            'archivo.required' => 'Debe adjuntar la minuta, orientación o presentación.',
            'archivo.mimes' => 'Solo se permiten archivos PDF, PPT, PPTX, DOC o DOCX.',
            'archivo.max' => 'El archivo no debe superar los 15 MB.',
        ];
    }
}
