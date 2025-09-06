<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CarrierRequest extends FormRequest
{
    /**
     * Indicates if the validator should stop on the first rule failure.
     *
     * @var bool
     */
    protected $stopOnFirstFailure = true;

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

        $rulesArray = [
            'whatsapp'                              => ['nullable', 'string', 'max:100', 'regex:/^[0-9]{8,}$/'],
            'phone'                                 => ['nullable', 'string', 'max:100', 'regex:/^[0-9]{8,}$/'],
            'address'                               => ['nullable', 'string', 'max:500'],
            'note'                                  => ['nullable', 'string', 'max:500'],
            'status'                                => ['required'],
        ];

        if ($this->isMethod('PUT')) {
            $carrierId                     = $this->input('id');
            $rulesArray['id']           = ['required'];
            $rulesArray['name']       = ['required', 'string', 'max:100', Rule::unique('carriers')->ignore($carrierId)];
            $rulesArray['mobile']       = ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8,}$/', Rule::unique('carriers')->ignore($carrierId)];
            $rulesArray['phone']       = ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8,}$/', Rule::unique('carriers')->ignore($carrierId)];
            $rulesArray['whatsapp']     = ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8,}$/', Rule::unique('carriers')->ignore($carrierId)];
            $rulesArray['email']        = ['nullable', 'email', 'max:100', 'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/', Rule::unique('carriers')->ignore($carrierId)];
        }else{
            $rulesArray['name']       = ['required', 'string', 'max:100', Rule::unique('carriers')];
            $rulesArray['mobile']       = ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8,}$/', Rule::unique('carriers')];
            $rulesArray['phone']       = ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8,}$/', Rule::unique('carriers')];
            $rulesArray['whatsapp']     = ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8,}$/', Rule::unique('carriers')];
            $rulesArray['email']        = ['nullable', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/', Rule::unique('carriers')];
        }

        return $rulesArray;

    }
    public function messages(): array
    {
        $responseMessages = [
            'name.required'     => 'اسم الناقل مطلوب',
            'status.required'   => 'يرجى اختيار الحالة',

            // Email validation messages
            'email.email'       => 'يرجى إدخال بريد إلكتروني صحيح',
            'email.regex'       => 'يجب أن يكون البريد الإلكتروني من Gmail فقط (مثال: user@gmail.com)',
            'email.unique'      => 'هذا البريد الإلكتروني مستخدم من قبل',

            // Phone number validation messages
            'mobile.regex'      => 'رقم الهاتف المحمول يجب أن يكون أرقاماً فقط ولا يقل عن 8 أرقام',
            'mobile.unique'     => 'رقم الهاتف المحمول مستخدم من قبل',

            'phone.regex'       => 'رقم الهاتف يجب أن يكون أرقاماً فقط ولا يقل عن 8 أرقام',
            'phone.unique'      => 'رقم الهاتف مستخدم من قبل',

            'whatsapp.regex'    => 'رقم الواتس اب يجب أن يكون أرقاماً فقط ولا يقل عن 8 أرقام',
            'whatsapp.unique'   => 'رقم الواتس اب مستخدم من قبل',
        ];

        if ($this->isMethod('PUT')) {
            $responseMessages['id.required']    = 'المعرف غير موجود لتحديث السجل';
        }

        return $responseMessages;
    }
}
