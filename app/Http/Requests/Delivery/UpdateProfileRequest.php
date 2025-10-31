<?php

namespace App\Http\Requests\Delivery;

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
        $userId = $this->user()->id;

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $userId,
            'mobile' => 'sometimes|required|string|unique:users,mobile,' . $userId . '|max:20',
            // Forbidden fields
            'role_id' => 'prohibited',
            'carrier_id' => 'prohibited',
            'status' => 'prohibited',
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
            'username.required' => 'اسم المستخدم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'mobile.required' => 'رقم الجوال مطلوب',
            'mobile.unique' => 'رقم الجوال مستخدم بالفعل',
            'role_id.prohibited' => 'لا يمكن تحديث هذا الحقل',
            'carrier_id.prohibited' => 'لا يمكن تحديث هذا الحقل',
            'status.prohibited' => 'لا يمكن تحديث هذا الحقل',
        ];
    }
}
