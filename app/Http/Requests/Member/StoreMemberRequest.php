<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'process_avatar' => 'nullable',
            'email' => [
                'required',
                'email',
                'max:96',
                'unique:members,email',
            ],
            'time_zone' => 'required|timezone',
            'new_password' => 'nullable|min:6|max:48',
            'current_password' => 'required|min:6|max:48',
        ];
    }
}
