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
            'order_code' => 'required|string|max:10|unique:orders,order_code',
            'user_id' => 'required|exists:users,id',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|in:Thanh toán khi nhận hàng,VN Pay,MOMO',
            'order_status' => 'in:Chờ xác nhận,Đã xác nhận,Giao hàng thành công,Đã huỷ',
            'payment_status' => 'required|in:Chưa thanh toán,Đã thanh toán',
            'order_address' => 'required|string|max:255',
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
            'order_code.required' => 'Mã đơn hàng là bắt buộc.',
            'order_code.string' => 'Mã đơn hàng phải là một chuỗi.',
            'order_code.max' => 'Mã đơn hàng không được vượt quá 10 ký tự.',
            'order_code.unique' => 'Mã đơn hàng này đã tồn tại.',

            'user_id.required' => 'Người dùng là bắt buộc.',
            'user_id.exists' => 'Người dùng không tồn tại.',

            'total_amount.required' => 'Số tiền là bắt buộc.',
            'total_amount.numeric' => 'Số tiền phải là một số.',

            'payment_method.required' => 'Phương thức thanh toán là bắt buộc.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',

            'order_status.in' => 'Trạng thái đơn hàng không hợp lệ.',

            'payment_status.required' => 'Trạng thái thanh toán là bắt buộc.',
            'payment_status.in' => 'Trạng thái thanh toán không hợp lệ.',

            'order_address.required' => 'Địa chỉ đơn hàng là bắt buộc.',
            'order_address.string' => 'Địa chỉ phải là một chuỗi.',
            'order_address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',

            'note.string' => 'Ghi chú phải là một chuỗi.',



            'order_details.required' => 'Chi tiết đơn hàng là bắt buộc.',
            'order_details.array' => 'Chi tiết đơn hàng phải là một mảng.',

            'order_details.*.product_id.required' => 'ID sản phẩm là bắt buộc.',
            'order_details.*.product_id.exists' => 'Sản phẩm không tồn tại.',

            'order_details.*.product_att_id.required' => 'ID thuộc tính sản phẩm là bắt buộc.',
            'order_details.*.product_att_id.exists' => 'Thuộc tính sản phẩm không tồn tại.',

            'order_details.*.size.required' => 'Kích thước là bắt buộc.',
            'order_details.*.size.string' => 'Kích thước phải là một chuỗi.',

            'order_details.*.color.required' => 'Màu sắc là bắt buộc.',
            'order_details.*.color.string' => 'Màu sắc phải là một chuỗi.',

            'order_details.*.product_name.required' => 'Tên sản phẩm là bắt buộc.',
            'order_details.*.product_name.string' => 'Tên sản phẩm phải là một chuỗi.',
            'order_details.*.product_name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',

            'order_details.*.unit_price.required' => 'Giá đơn vị là bắt buộc.',
            'order_details.*.unit_price.numeric' => 'Giá đơn vị phải là một số.',
            'order_details.*.unit_price.min' => 'Giá đơn vị phải lớn hơn hoặc bằng 0.',

            'order_details.*.total_amount.required' => 'Tổng tiền là bắt buộc.',
            'order_details.*.total_amount.numeric' => 'Tổng tiền phải là một số.',
            'order_details.*.total_amount.min' => 'Tổng tiền phải lớn hơn hoặc bằng 0.',

            'order_details.*.quantity.required' => 'Số lượng là bắt buộc.',
            'order_details.*.quantity.integer' => 'Số lượng phải là một số nguyên.',
            'order_details.*.quantity.min' => 'Số lượng phải lớn hơn hoặc bằng 1.',

            'order_details.*.thumbnail.required' => 'Hình ảnh là bắt buộc.',
            'order_details.*.thumbnail.string' => 'Hình ảnh phải là một chuỗi.',
            'order_details.*.thumbnail.max' => 'Hình ảnh không được vượt quá 255 ký tự.',
        ];
    }

}
