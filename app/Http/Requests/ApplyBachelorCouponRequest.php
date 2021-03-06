<?php

namespace App\Http\Requests;

class ApplyBachelorCouponRequest extends UserFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'dateId' => 'integer|required',
        ];
    }
}
