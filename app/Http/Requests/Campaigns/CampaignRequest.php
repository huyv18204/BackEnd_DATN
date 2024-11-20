<?php

namespace App\Http\Requests\Campaigns;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
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
        $id = $this->route('id');
       
        return [
            'name' => "required|string|unique:campaigns,name,{$id}",
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên chiến dịch là bắt buộc.',
            'name.unique' => 'Tên chiến dịch đã tồn tại.',
            'discount_percentage.required' => 'Phần trăm giảm giá là bắt buộc.',
            'discount_percentage.numeric' => 'Phần trăm giảm giá phải là số.',
            'discount_percentage.min' => 'Phần trăm giảm giá phải lớn hơn hoặc bằng 0.',
            'discount_percentage.max' => 'Phần trăm giảm giá không thể lớn hơn 100.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.after_or_equal' => 'Ngày bắt đầu phải là ngày hôm nay hoặc trong tương lai.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'status.in' => 'Trạng thái chiến dịch không hợp lệ.',
            'is_active.boolean' => 'Cờ trạng thái hoạt động phải là boolean.',
        ];
    }
}
