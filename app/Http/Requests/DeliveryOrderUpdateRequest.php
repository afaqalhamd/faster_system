<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryOrderUpdateRequest extends FormRequest
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
            'note' => 'nullable|string|max:1000',
            'shipping_charge' => 'nullable|numeric|min:0',
            'is_shipping_charge_distributed' => 'nullable|boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'note.max' => 'Notes must not exceed 1000 characters',
            'shipping_charge.numeric' => 'Shipping charge must be a number',
            'shipping_charge.min' => 'Shipping charge must be at least 0',
            'is_shipping_charge_distributed.boolean' => 'Shipping charge distribution must be a boolean value',
        ];
    }
}
