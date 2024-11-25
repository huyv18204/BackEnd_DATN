<?php

namespace App\Http\Requests\ShippingAddress;

use Illuminate\Foundation\Http\FormRequest;

class ShippingAddressRequest extends FormRequest
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
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => [
                'required',
                'string',
                'max:11',
                'regex:/^(?:\+84|0)(1[0-9]|9[0-3]|9[0-9]|8[1-9]|7[0-9]|6[0-9]|5[0-9]|4[0-9])\d{7}$/',
            ],
            'recipient_address' => 'required|string|max:255',
            'province.code' => 'required|string',
            'province.name' => 'required|string|max:255',
            'district.code' => 'required|string',
            'district.name' => 'required|string|max:255',
            'ward.code' => 'required|string',
            'ward.name' => 'required|string|max:255',
            'is_default' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute là bắt buộc',
            'string' => ':attribute phải là chuỗi',
            'max' => ':attribute vượt quá :max kí tự',
            'integer' => ':attribute phải là số',
            'boolean' => ':attribute phải là kiểu boolean',
            'regex' => ':attribute không hợp lệ'
        ];
    }

    public function attributes(): array
    {
        return [
            'recipient_name' => "Tên người nhận",
            'recipient_address' => "Địa chỉ nhận",
            'recipient_phone' => "Số điện thoại",
            'province.name' => "Tỉnh",
            'province.code' => "Mã tỉnh",
            'district.name' => "Tỉnh",
            'district.code' => "Mã tỉnh",
            'ward.name' => "Tỉnh",
            'ward.code' => "Mã tỉnh",
            'is_default' => "Mặc định"
        ];
    }
}
