<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ClassBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gym_class_schedule_id' => ['required', 'integer', 'exists:gym_class_schedules,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'gym_class_schedule_id.required' => 'Vui lòng chọn lịch học lớp.',
            'gym_class_schedule_id.exists' => 'Lịch học lớp không tồn tại.',
        ];
    }
}
