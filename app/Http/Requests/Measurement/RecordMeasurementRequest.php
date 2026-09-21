<?php

namespace App\Http\Requests\Measurement;

use Illuminate\Foundation\Http\FormRequest;

class RecordMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'measured_at' => ['nullable', 'date'],
            'weight_kg' => ['required', 'numeric', 'min:20', 'max:300'],
            'height_cm' => ['required', 'numeric', 'min:100', 'max:250'],
            'body_fat_percentage' => ['nullable', 'numeric', 'min:1', 'max:70'],
            'muscle_mass_kg' => ['nullable', 'numeric', 'min:10', 'max:150'],
            'chest_cm' => ['nullable', 'numeric', 'min:40', 'max:200'],
            'waist_cm' => ['nullable', 'numeric', 'min:40', 'max:200'],
            'hips_cm' => ['nullable', 'numeric', 'min:40', 'max:200'],
            'arms_cm' => ['nullable', 'numeric', 'min:15', 'max:80'],
            'thighs_cm' => ['nullable', 'numeric', 'min:30', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'weight_kg.required' => 'Vui lòng nhập cân nặng (kg).',
            'height_cm.required' => 'Vui lòng nhập chiều cao (cm).',
        ];
    }
}
