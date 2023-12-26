<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UserRegistrationRequest extends FormRequest
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
            'fname' => 'required',
            'lname' => 'required',
            'email' => 'required|string|email|max:100',
            'phone' => 'required',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'user_type' => 'required|numeric|between:0,1',
            "category" => 'required_if:user_type,1',
            'address' => 'required_if:user_type,1',
            'city' => 'required_if:user_type,1',
            'state' => 'required_if:user_type,1',
            'zipcode' => 'required_if:user_type,1',
            "address" => 'required_if:user_type,0'
        ];
    }

    public function messages(): array
    {
        $type = $this->get('user_type') === 1 ? 'provider' : 'customer';
        return [
            'category.required_if' => "The :attribute field is required when user type is {$type}",
            'address.required_if' => "The :attribute field is required when user type is {$type}",
            'city.required_if' => "The :attribute field is required when user type is {$type}",
            'state.required_if' => "The :attribute field is required when user type is {$type}",
            'zipcode.required_if' => "The :attribute field is required when user type is {$type}",
            'address.required_if' => "The :attribute field is required when user type is {$type}",
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, new JsonResponse($validator->errors()));
    }
 
    public function withValidator(Validator $validator)
    {
        // $validate = \Validator::make($this->all(), [
        //     'category' => 'required',
        //     'address' => 'required',
        //     'city' => 'required',
        //     'state' => 'required',
        //     'zipcode' => 'required'
        // ]);
        // if ($validator->fails())
        //     throw new ValidationException($validator, new JsonResponse($validate->errors()));

        // return false;
        //     $validator->after(function ($validator) {
        //         if ($this->user_type == '1') {
        //             $validator->errors()->add('category', 'Category field is required.');
        //             $validator->errors()->add('address', 'Address field is required.', 'required|max:2');
        //         }
        //     });
    }
}
