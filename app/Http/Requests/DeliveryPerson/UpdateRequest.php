<?php

namespace App\Http\Requests\DeliveryPerson;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'personal.email' => 'required|email|max:255',
            'personal.phone_number' => 'required|string|max:10',
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
            'personal.phone_number.max' => 'Số điện thoại không quá 10 kí tự.',
            'personal.address.required' => 'Địa chỉ là bắt buộc.',
            'vehicle.vehicle_name.required' => 'Tên phương tiện là bắt buộc.',
            'vehicle.license_plate.required' => 'Biển số xe là bắt buộc.',
        ];
    }
}
