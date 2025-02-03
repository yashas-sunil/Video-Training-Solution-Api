<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
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
            'name' => 'required|max:255',
            'mobile_code' => 'required',
            'age' => 'required|numeric|digits_between:1,10',
            'course_id' => 'required|max:11',
            'level_id' => 'required|max:11',
            'attempt_year' => 'required|max:255',
            'address' => 'required|max:255',
            'country_id' => 'required|max:11',
            'state_id' => 'required|max:11',
            'city' => 'required|max:255',
            'pin' => 'required|max:255',
        ];
    }
}
