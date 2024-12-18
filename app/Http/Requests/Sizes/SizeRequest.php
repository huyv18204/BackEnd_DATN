<?php

namespace App\Http\Requests\Sizes;

use Illuminate\Foundation\Http\FormRequest;

class SizeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:55|unique:sizes,name',
            'is_active' => 'boolean',
        ];

        if ($this->isMethod('put')) {
            $rules['name'] = 'required|string|max:55|unique:sizes,name,' . $this->route('id');
        }

        return $rules;
    }

    public function attributes()
    {
        return [
            'name' => 'Tên kích thước',
        ];
    }
}
