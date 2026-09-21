<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'], // 100MB max
            'collection' => ['required', 'string', 'in:avatar,exercise_video,exercise_thumb,inbody_front,inbody_side,inbody_back,class_cover,pt_cert'],
            'mediable_type' => ['nullable', 'string'],
            'mediable_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn tệp để tải lên.',
            'file.max' => 'Tệp tin không được vượt quá 100MB.',
            'collection.required' => 'Vui lòng chỉ định danh mục media.',
            'collection.in' => 'Danh mục media không hợp lệ.',
        ];
    }
}
