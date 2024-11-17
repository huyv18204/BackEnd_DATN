<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'total_amount' => 'required|integer|numeric|min:0',
            'shipping_address_id' => 'required|integer|exists:shipping_addresses,id',
            'note' => 'nullable|string',
            'order_details' => 'required|array',
            'order_details.*.product_id' => 'required|exists:products,id',
            'order_details.*.product_att_id' => 'required|exists:product_atts,id',
            'order_details.*.size' => 'required|string',
            'order_details.*.color' => 'required|string',
            'order_details.*.product_name' => 'required|string|max:255',
            'order_details.*.unit_price' => 'required|numeric|min:0',
            'order_details.*.total_amount' => 'required|numeric|min:0',
            'order_details.*.quantity' => 'required|integer|min:1',
            'order_details.*.thumbnail' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'required' => ':attribute là bắt buộc',
            'integer' => ':attribute phải là số',
            'numeric' => ':attribute phải là số',
            'exists' => ':attribute không ồn tại',
            'string' => ':attribute phải là chuỗi',
            'max' => ':attribute không được vượt quá :values'

        ];
    }

    public function attributes() {
        return [
            'total_amount' => 'Tổng tiền đơn hàng',
            'shipping_address_id' => 'Địa chỉ đặt hàng',
            'note' => 'Ghi chú',
            'order_details' => 'Chi tiết đơn hàng',
            'order_details.*.product_id' => 'Sản phẩm',
            'order_details.*.product_att_id' => 'Biến thể',
            'order_details.*.size' => 'Kích cỡ',
            'order_details.*.color' => 'Màu',
            'order_details.*.product_name' => 'Tên sản phẩm',
            'order_details.*.unit_price' => 'Đơn giá',
            'order_details.*.total_amount' => 'Tổng số tiền',
            'order_details.*.quantity' => "Số lượng",
            'order_details.*.thumbnail' => 'Ảnh',


        ];
    }

}
