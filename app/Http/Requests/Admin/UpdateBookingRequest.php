<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
            'duration' => ['required'],
            'price' => ['required'],
            'booked_at' => ['required', 'after:today'],
            'start_at' => ['required', 'after:today'],
            'end_at' => ['required', 'date_format:H:i:s', 'after:start_at'],
        ];
    }
}
