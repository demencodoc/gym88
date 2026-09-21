<?php

namespace App\Http\Requests\Workout;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'workout_plan_day_id' => ['nullable', 'integer', 'exists:workout_plan_days,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
