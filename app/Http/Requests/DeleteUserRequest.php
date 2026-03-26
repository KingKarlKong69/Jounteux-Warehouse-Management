<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'confirm_name' => ['required', 'string'],
            'admin_password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_name.required' => 'You must type the user\'s full name to confirm.',
            'admin_password.required' => 'Your admin password is required to delete a user.',
        ];
    }
}
