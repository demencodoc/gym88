<?php

namespace App\Http\Requests\CheckIn;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required_without:user_id', 'nullable', 'string'],
            'user_id' => ['required_without:code', 'nullable', 'integer', 'exists:users,id'],
            'method' => ['nullable', 'string', 'in:dynamic_qr,membership_code,manual'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required_without' => 'Vui lòng cung cấp mã QR hoặc mã thẻ hội viên.',
            'user_id.required_without' => 'Vui lòng cung cấp mã hội viên hoặc ID người dùng.',
        ];
    }
}
