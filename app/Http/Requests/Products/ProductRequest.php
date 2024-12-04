<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        $productId = $this->route('id');
        $rules =  [
            'material' => 'nullable|string|max:255',
            'name' => 'required|string|max:55|unique:products,name',
            'thumbnail' => 'required|max:255',
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'regular_price' => 'required|numeric|min:0',
            'reduced_price' => 'nullable|numeric|min:0|lt:regular_price',
            'category_id' => 'required|exists:categories,id',
            'is_active' => 'nullable|boolean',

            'product_att' => 'required|array',
            'product_att.*.size_id' => 'nullable|exists:sizes,id',
            'product_att.*.color_id' => 'nullable|exists:colors,id',
            'product_att.*.image' => 'nullable',
            'product_att.*.regular_price' => 'nullable|numeric|min:0',
            'product_att.*.reduced_price' => 'nullable|numeric|min:0|lt:regular_price',
            'product_att.*.stock_quantity' => 'required|integer|min:0',
        ];

        if ($this->isMethod('put') && $productId) {
            $rules = [
                'material' => 'nullable|string|max:255',
                'name' => 'required|string|max:55|unique:products,name,' . $productId,
                'thumbnail' => 'required|string|max:255',
                'short_description' => 'nullable|string',
                'long_description' => 'nullable|string',
                'regular_price' => 'required|numeric|min:0',
                'reduced_price' => 'nullable|numeric|min:0|lt:regular_price',
                'category_id' => 'required|exists:categories,id',
                'is_active' => 'nullable|boolean',
            ];
        }

        return $rules;
    }

    /**
     * Lấy các thuộc tính tùy chỉnh cho các trường.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'material' => 'Chất liệu',
            'name' => 'Tên sản phẩm',
            'thumbnail' => 'Ảnh đại diện',
            'short_description' => 'Mô tả ngắn',
            'gallery' => 'Ảnh sản phẩm',
            'long_description' => 'Mô tả chi tiết',
            'regular_price' => 'Giá thường',
            'reduced_price' => 'Giá giảm',
            'category_id' => 'Danh mục sản phẩm',
            'is_active' => 'Trạng thái sản phẩm',
            'product_variants' => 'Biến thể sản phẩm',
            'product_variants.*.size_id' => 'Kích cỡ',
            'product_variants.*.color_id' => 'Màu sắc',
            'product_variants.*.stock_quantity' => 'Số lượng tồn kho',
            'product_color_images' => 'Ảnh đại diện theo màu',
            'product_color_images.*.color_id' => 'Màu sắc',
            'product_color_images.*.image' => 'Ảnh màu sắc',
        ];
    }
}
