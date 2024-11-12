<?php

namespace App\Http\Requests\Color;

use Illuminate\Foundation\Http\FormRequest;

class ColorRequest extends FormRequest
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
        $rules = [
            'name' => 'required|string|max:55|unique:colors,name', // Đảm bảo name là duy nhất khi tạo
            'code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ];

        if ($this->isMethod('put')) {
            $rules['name'] = 'required|string|max:55|unique:colors,name,' . $this->route('id');
        }

        return $rules;
    }

    public function attributes()
    {
        return [
            'name' => 'Tên màu sắc',
            'code' => 'Mã màu',
        ];
    }
}
