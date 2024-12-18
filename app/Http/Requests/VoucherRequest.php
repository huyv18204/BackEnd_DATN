<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoucherRequest extends FormRequest
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
        $url = $this->url();
        if (str_contains($url, 'apply')) {
            return [
                'voucher_code' => 'required|string',
                'order_total'  => 'required|numeric|min:0',
            ];
        }

        if ($this->isMethod('put')) {
            return [
                'voucher_code' => 'nullable|string|unique:vouchers,voucher_code,' . $this->route('id'),
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'discount_type' => 'required|in:percentage,fixed_amount',
                'discount_value' => 'required|numeric|min:0',
                'max_discount' => 'nullable|numeric|min:0',
                'min_order_value' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:0',
                'used_count' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
                'start_date' => 'nullable|date|after_or_equal:' . now(),
                'end_date' => 'nullable|date|after:start_date',
                'status' => 'nullable|string|max:255',
            ];
        }

        return [
            'voucher_code' => 'nullable|string|unique:vouchers,voucher_code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'used_count' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date|after_or_equal:' . now(),
            'end_date' => 'nullable|date|after:start_date',
        ];
    }

    public function messages()
    {
        return [
            'start_date.after_or_equal' => 'Thời gian bắt đầu phải lớn hơn hoặc bằng thời gian hiện tại',
            'end_date.after' => 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu',
        ];
    }
    public function attributes()
    {
        return [
            'voucher_code' => 'Mã giảm giá',
            'name' => 'Tên voucher',
            'description' => 'Mô tả',
            'discount_type' => 'Loại giảm giá',
            'discount_value' => 'Giá trị giảm giá',
            'max_discount' => 'Giảm trị giảm tối đa',
            'min_order_value' => 'Giá trị đơn hàng tối thiểu',
            'usage_limit' => 'Giới hạn sử dụng',
            'used_count' => 'Số lần đã sử dụng',
            'is_active' => 'Trạng thái kích hoạt',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'status' => 'Trạng thái',
            'voucher_code' => 'Mã giảm giá',
            'order_total' => 'Tổng tiền',
        ];
    }
}
