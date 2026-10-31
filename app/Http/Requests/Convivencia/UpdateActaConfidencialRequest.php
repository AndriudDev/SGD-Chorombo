<?php

namespace App\Http\Requests\Convivencia;

use App\Models\ActaConfidencial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActaConfidencialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'categoria' => ['required', Rule::in(array_keys(ActaConfidencial::CATEGORIAS))],
            'ambito' => ['required', Rule::in(array_keys(ActaConfidencial::AMBITOS))],
            'estudiante_nombre' => ['required', 'string', 'max:255'],
            'estudiante_run' => ['required', 'string', 'max:12'],
            'apoderado_nombre' => ['required', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título del acta es obligatorio.',
            'categoria.required' => 'La categoría de resguardo es obligatoria.',
            'categoria.in' => 'La categoría debe ser Confidencial o Sensible.',
            'ambito.required' => 'El ámbito del acuerdo es obligatorio.',
            'ambito.in' => 'El ámbito debe ser conductual, pedagógico o administrativo.',
            'estudiante_nombre.required' => 'El nombre del estudiante es obligatorio.',
            'estudiante_run.required' => 'El RUN del estudiante es obligatorio.',
            'apoderado_nombre.required' => 'El nombre del apoderado es obligatorio.',
            'fecha.required' => 'La fecha del acta es obligatoria.',
            'archivo.mimes' => 'Solo se permiten archivos PDF, JPG, PNG, DOC o DOCX.',
            'archivo.max' => 'El archivo no debe superar los 15 MB.',
        ];
    }
}
