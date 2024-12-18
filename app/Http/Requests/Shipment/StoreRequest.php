<?php

namespace App\Http\Requests\Shipment;

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

    public function rules(): array
    {
        return [
            'delivery_person_id' => ['required', 'integer', 'exists:delivery_people,id'],
            'orders' => ['required', 'array', 'min:1', 'max:5'],
            'orders.*.order_id' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_person_id.required' => 'Trường delivery_person_id là bắt buộc.',
            'delivery_person_id.exists' => 'Người giao hàng không tồn tại.',
            'orders.required' => 'Phải có ít nhất một đơn hàng.',
            'orders.max' => "Nhiều nhất là 5 đơn hàng trong 1 lô hàng" ,
            'orders.*.order_id.required' => 'Mã lô hàng là bắt buộc.',
            'orders.*.order_id.exists' => 'Mã lô hàng không tồn tại.',
        ];
    }
}
