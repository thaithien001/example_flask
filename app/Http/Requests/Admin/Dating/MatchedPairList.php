<?php

namespace App\Http\Requests\Admin\Dating;

use Illuminate\Foundation\Http\FormRequest;

class MatchedPairList extends FormRequest
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
            'weekOffSet'=>'integer',
            'status'=>'integer',
            'search'=>'string|nullable',
            'isFake'=>'integer|nullable',
            'datingDate'=>'string|nullable',
            'startTime'=>'string|nullable',
        ];
    }
}
