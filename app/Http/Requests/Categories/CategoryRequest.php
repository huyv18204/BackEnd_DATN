<?php

namespace App\Http\Requests\Categories;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'image' => 'nullable',
            'parent_id.*' => 'nullable|exists:categories,id', 
            'is_active' => 'boolean',
        ];

        return $rules;
    }
    public function attributes()
    {
        return [
            'name' => 'Tên danh mục',
            'parent_id' => 'Danh mục cha',
            'is_active' => 'Trạng thái',
        ];
    }
}
