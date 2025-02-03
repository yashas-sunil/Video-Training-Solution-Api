<?php

namespace App\Http\Requests;

use App\Models\ProfessorNote;
use Illuminate\Foundation\Http\FormRequest;

class StoreProfessorNoteRequest extends FormRequest
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
            'video_id' => 'required|numeric|digits_between:1,10',
            'name' => 'max:255',
            'description' => '',
            'time' => 'numeric|digits_between:1,10',
        ];
    }
}
