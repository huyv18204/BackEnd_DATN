<?php

namespace App\Http\Requests\ProductAtts;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            '*.color_id' => 'nullable|integer|exists:colors,id',
            '*.image' => 'nullable|string',
            '*.regular_price' => 'required|numeric|min:0',
            '*.reduced_price' => 'nullable|numeric|min:0|lt:regular_price',
            '*.size_id' => 'nullable|integer|exists:sizes,id',
            '*.stock_quantity' => 'required|integer|min:0',
        ];
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
