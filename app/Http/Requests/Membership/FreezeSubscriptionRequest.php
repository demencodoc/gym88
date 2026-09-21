<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;

class FreezeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => ['nullable', 'integer', 'exists:member_subscriptions,id'],
            'suspended_at' => ['nullable', 'date'],
            'suspended_until' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'suspended_until.required' => 'Vui lòng chọn ngày kết thúc bảo lưu.',
            'suspended_until.after_or_equal' => 'Ngày kết thúc bảo lưu phải từ hôm nay trở đi.',
        ];
    }
}
