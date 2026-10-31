<?php

namespace App\Http\Requests\Convivencia;

use App\Models\Citacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCitacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estudiante_nombre' => ['required', 'string', 'max:255'],
            'estudiante_run' => ['required', 'string', 'max:12'],
            'apoderado_nombre' => ['required', 'string', 'max:255'],
            'funcionario_cita' => ['required', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'motivo' => ['required', Rule::in(array_keys(Citacion::MOTIVOS))],
            'archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'estudiante_nombre.required' => 'El nombre del estudiante es obligatorio.',
            'estudiante_run.required' => 'El RUN del estudiante es obligatorio.',
            'apoderado_nombre.required' => 'El nombre del apoderado es obligatorio.',
            'funcionario_cita.required' => 'El funcionario que cita es obligatorio.',
            'fecha.required' => 'La fecha de la citación es obligatoria.',
            'hora.required' => 'La hora de la citación es obligatoria.',
            'hora.date_format' => 'La hora debe tener formato HH:MM (24h).',
            'motivo.required' => 'El motivo de la citación es obligatorio.',
            'motivo.in' => 'El motivo debe ser conductual, pedagógico o administrativo.',
            'archivo.mimes' => 'Solo se permiten archivos PDF, JPG, PNG, DOC o DOCX.',
            'archivo.max' => 'El archivo no debe superar los 15 MB.',
        ];
    }
}
