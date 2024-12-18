<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:250',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|regex:/^\d{10,11}$/|unique:users,phone',
            'address' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|string|min:6|confirmed',
            "password_confirmation" => 'required',
            'role' => 'nullable'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên người dùng',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'address' => 'Địa chỉ',
            'date_of_birth' => 'Ngày sinh',
            'avatar' => 'Ảnh đại diện',
            'password' => 'Mật khẩu',
            "password_confirmation" => 'Xác nhận mật khẩu',
            'role' => 'Vai trò'
        ];
    }
}
