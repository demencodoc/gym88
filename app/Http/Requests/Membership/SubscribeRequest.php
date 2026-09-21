<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:membership_packages,id'],
            'start_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'in:vietqr,vnpay,momo,cash,bank_transfer'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => 'Vui lòng chọn gói tập.',
            'package_id.exists' => 'Gói tập được chọn không tồn tại.',
        ];
    }
}
