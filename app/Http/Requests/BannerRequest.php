<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|string',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date|after_or_equal:' . now(),
            'end_date' => 'nullable|date|after:start_date',
        ];
    }

    public function messages()
    {
        return [
            'start_date.after_or_equal' => "Thời gian bắt đầu phải lớn hơn hoặc bằng thời gian hiện tại",
            'end_date.after' => 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Tiêu đề',
            'description' => 'Mô tả',
            'image' => 'Hình ảnh',
            'priority' => 'Thứ tự ưu tiên',
            'is_active' => 'Trạng thái hoạt động',
            'start_date' => 'Thời gian bắt đầu',
            'end_date' => 'Thời gian kết thúc',
        ];
    }
}
