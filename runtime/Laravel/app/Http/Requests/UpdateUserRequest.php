<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('user.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        
        return [
           'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'name')

                    ->ignore($user->uuid, 'uuid'),
            ],
            'uuid' => 'required',

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
                Rule::unique('users', 'username')->ignore($user->uuid, 'uuid'),
                'regex:/^[a-z0-9_]+$/',
            ],

            // 'password' => [
            //     'required',
            //     'string',
            //     'min:8',
            // ],
            'status'    => ['boolean'],
            'role_id'   => ['required', 'exists:roles,id'],

        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'The username may only contain (a-z, 0-9, _) characters.',
            'username.unique' => 'This username is already taken.',
        ];
    }
}
