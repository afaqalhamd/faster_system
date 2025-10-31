<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'otp' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'otp.required' => 'رمز التحقق مطلوب',
            'otp.string' => 'رمز التحقق يجب أن يكون نص',
            'otp.size' => 'رمز التحقق يجب أن يكون 6 أرقام',
            'otp.regex' => 'رمز التحقق يجب أن يحتوي على أرقام فقط',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
