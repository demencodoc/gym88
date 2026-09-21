<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:membership_packages,id'],
            'payment_method' => ['nullable', 'string', 'in:vietqr,momo,vnpay,cash,bank_transfer'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => 'Vui lòng chọn gói tập để thanh toán.',
            'package_id.exists' => 'Gói tập không tồn tại.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ];
    }
}
