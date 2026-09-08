<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('user.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:users,name',
            ],
            'uuid' => 'required|uuid|unique:users,uuid',

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],

            'username' => [
                'required',
                'string',
                'max:255',
                'unique:users,username',
                'regex:/^[a-z0-9_]+$/',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

             'phone_number' => ['nullable', 'string', 'max:20'],
             'role_id' => ['required', 'exists:roles,id'],

        ];
    }

    public function messages(): array
    {
        return [
            'uuid.unique'    => __('messages.common.uuid'),
            'username.regex' => 'The username must only contain lowercase letters, numbers, and underscores (no spaces or capital letters).',
            'username.unique' => 'This username is already taken.',
        ];
    }
}
