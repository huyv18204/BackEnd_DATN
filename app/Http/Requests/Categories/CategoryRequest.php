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
        $id = $this->route('id');
    
        if ($this->isMethod('PUT')) {
            return [
                'name' => 'required|string|max:255|unique:categories,name,' . $id,
                'image' => 'nullable',
                'is_active' => 'boolean',
            ];
        }
    
        return [
            'name' => 'required|string|max:255|unique:categories,name',
            'image' => 'nullable',
            'is_active' => 'boolean',
        ];
    }
    
    public function attributes()
    {
        return [
            'name' => 'Tên danh mục',
            'is_active' => 'Trạng thái',
        ];
    }
}
