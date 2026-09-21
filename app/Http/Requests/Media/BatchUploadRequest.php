<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class BatchUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body_measurement_id' => ['required', 'integer', 'exists:body_measurements,id'],
            'front' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'side' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'back' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'body_measurement_id.required' => 'Vui lòng chỉ định bản ghi đo lường InBody.',
            'front.max' => 'Ảnh mặt trước không vượt quá 10MB.',
            'side.max' => 'Ảnh mặt bên không vượt quá 10MB.',
            'back.max' => 'Ảnh mặt sau không vượt quá 10MB.',
        ];
    }
}
