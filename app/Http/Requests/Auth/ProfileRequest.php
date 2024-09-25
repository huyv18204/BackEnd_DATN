<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'name' => 'string|max:255',
            'phone' => 'string|unique:users,phone,' . auth()->user()->id,
            'address' => 'string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Tên người dùng',
            'address' => 'Địa chỉ',
            'phone' => 'Số điện thoại',
            'date_of_birth' => 'Ngày sinh',
            'avatar' => 'Ảnh đại diện',
        ];
    }
}
