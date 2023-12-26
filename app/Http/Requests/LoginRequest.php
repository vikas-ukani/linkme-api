<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'email' => 'required',
            'password' => 'required',
            'user_type' => 'required',
            'device_id' => 'required',
            'device_type' => 'required',
            'device_token' => 'required'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, new JsonResponse($validator->errors()));
    }
}
