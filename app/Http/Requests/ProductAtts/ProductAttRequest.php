<?php

namespace App\Http\Requests\ProductAtts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ProductAttRequest extends FormRequest
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
        if ($this->isMethod('put')) {
            return [
                'image' => 'nullable|string',
                'regular_price' => 'nullable|numeric|min:0',
                'reduced_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
            ];
        }
        return [
            '*.color_id' => 'nullable|integer|exists:colors,id',
            '*.image' => 'nullable|string',
            '*.regular_price' => 'nullable|numeric|min:0',
            '*.reduced_price' => 'nullable|numeric|min:0',
            '*.size_id' => 'nullable|integer|exists:sizes,id',
            '*.stock_quantity' => 'required|integer|min:0',
        ];
    }

    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->input() as $key => $value) {
                if (isset($value['regular_price'], $value['reduced_price']) && $value['reduced_price'] >= $value['regular_price']) {
                    $validator->errors()->add("$key.reduced_price", 'Giá giảm phải nhỏ hơn giá gốc.');
                }
            }
        });
    }

    public function attributes()
    {
        return  [
            '*.color_id' => 'Màu sắc',
            '*.image' => 'Ảnh biến thể',
            '*.sizes.*.size_id' => 'Kích thước',
            '*.sizes.*.stock_quantity' => 'Tồn kho',
            '*.regular_price' => 'Giá bán thường',
            '*.reduced_price' => 'Giá giảm'
        ];
    }
}
