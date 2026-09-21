<?php

namespace App\Http\Requests\Workout;

use Illuminate\Foundation\Http\FormRequest;

class LogSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'set_number' => ['required', 'integer', 'min:1'],
            'reps_completed' => ['required', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_completed' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'exercise_id.required' => 'Vui lòng chọn bài tập.',
            'set_number.required' => 'Vui lòng nhập số set.',
            'reps_completed.required' => 'Vui lòng nhập số reps hoàn thành.',
        ];
    }
}
