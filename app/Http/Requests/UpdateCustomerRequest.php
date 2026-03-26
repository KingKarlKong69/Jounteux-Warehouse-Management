<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize phone: strip non-digits, remove leading 0.
     */
    protected function prepareForValidation(): void
    {
        if ($this->phone) {
            $digits = preg_replace('/\D/', '', $this->phone);
            $digits = ltrim($digits, '0');
            $this->merge(['phone' => $digits]);
        }
    }

    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'customer_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'phone' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('customers', 'phone')->ignore($customerId),
            ],
            'address' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Customer name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'A customer with this email already exists.',
            'phone.required' => 'Phone number is required.',
            'phone.digits' => 'Phone number must be exactly 10 digits.',
            'phone.unique' => 'A customer with this phone number already exists.',
        ];
    }
}
