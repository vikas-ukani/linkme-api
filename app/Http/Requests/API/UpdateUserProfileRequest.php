<?php

namespace App\Http\Requests\API;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UpdateUserProfileRequest extends FormRequest
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
            'fname' => 'string',
            'lname' => 'string',
            'email' => 'string|email',
            'phone' => 'string',
            'address' => 'string',
            'city' => 'string',
            'state' => 'string',
            'category' => 'string',
            'zipcode' => 'numeric',
            'bio' => 'string',
            'latitude' => 'numeric|between:-90,90',
            'longitude' => 'numeric|between:-90,90',
            'preference_location' => 'string',
            "is_in_home_service" => 'boolean',
            "service_location_lat" => 'numeric|between:-90,90',
            "service_location_long" => 'numeric|between:-90,90',
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, new JsonResponse($validator->errors()));
    }

}
