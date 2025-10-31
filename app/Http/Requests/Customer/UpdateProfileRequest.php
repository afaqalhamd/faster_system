<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        $partyId = $this->user()->id;

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:parties,email,' . $partyId,
            'mobile' => 'sometimes|required|string|unique:parties,mobile,' . $partyId . '|max:20',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            // Forbidden fields
            'to_pay' => 'prohibited',
            'to_receive' => 'prohibited',
            'credit_limit' => 'prohibited',
            'status' => 'prohibited',
            'party_type' => 'prohibited',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required' => 'اسم العائلة مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'mobile.required' => 'رقم الجوال مطلوب',
            'mobile.unique' => 'رقم الجوال مستخدم بالفعل',
            'to_pay.prohibited' => 'لا يمكن تحديث هذا الحقل',
            'to_receive.prohibited' => 'لا يمكن تحديث هذا الحقل',
            'credit_limit.prohibited' => 'لا يمكن تحديث هذا الحقل',
        ];
    }
}
