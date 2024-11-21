<?php

namespace App\Http\Requests\DeliveryPerson;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'personal.name' => 'required|string|max:255',
            'personal.email' => 'required|email|max:255|unique:users,email',
            'personal.phone_number' => [
                'required',
                'regex:/^(?:\+84|0)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-9]|9[0-9])\d{7}$/',
            ],
            'personal.address' => 'required|string|max:255',

            'vehicle.vehicle_name' => 'required|string|max:255',
            'vehicle.license_plate' => 'required|string|max:20',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'personal.name.required' => 'Tên là bắt buộc.',
            'personal.email.required' => 'Email là bắt buộc.',
            'personal.email.email' => 'Email phải là định dạng hợp lệ.',
            'personal.phone_number.required' => 'Số điện thoại là bắt buộc.',
            'personal.phone_number.regex' => 'Số điện thoại không hợp lệ.',
            'personal.address.required' => 'Địa chỉ là bắt buộc.',
            'personal.email.unique' => "Email đã tồn tại",

            'vehicle.vehicle_name.required' => 'Tên phương tiện là bắt buộc.',
            'vehicle.license_plate.required' => 'Biển số xe là bắt buộc.',
        ];
    }
}
