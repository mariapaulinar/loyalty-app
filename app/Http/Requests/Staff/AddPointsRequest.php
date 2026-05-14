<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class AddPointsRequest extends FormRequest
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
        if ($this->input('points_only') == 1) {
            return [
                'points' => 'required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:51200',
                'note' => 'nullable|max:1024',
                'points_only' => 'required|boolean',
            ];
        } else {
            return [
                'purchase_amount' => 'required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:51200',
                'note' => 'nullable|max:1024',
                'points_only' => 'required|boolean',
            ];
        }
    }
}
