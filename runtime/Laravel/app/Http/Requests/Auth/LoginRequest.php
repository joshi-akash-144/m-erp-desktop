<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // public function authenticate(): void
    // {
    //     $this->ensureIsNotRateLimited();

    //     if (!Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
    //         RateLimiter::hit($this->throttleKey());

    //         throw ValidationException::withMessages([
    //             'email' => trans('auth.failed'),
    //         ]);
    //     }
    //     RateLimiter::clear($this->throttleKey());
    // }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        // Find user by email only
        $user = User::where('email', $this->email)->first();


        // Check if user exists and password matches
        if (!$user || !Hash::check($this->password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Check if user is approved
        if (!$user->is_approved) {
            throw ValidationException::withMessages([
                'email' => trans('messages.login.unapproved'),
            ]);
        }

        // Check if user is active
        if (!$user->status) {
            throw ValidationException::withMessages([
                'email' => trans('messages.login.inactive'),
            ]);
        }

        // Check if user already has an active session
        $alreadyLoggedIn = UserLoginSession::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('sessions')
                    ->whereColumn('sessions.id', 'user_login_sessions.session_id');
            })
            ->exists();

        if ($alreadyLoggedIn) {
            throw ValidationException::withMessages([
                'email' => 'This account is already logged in from another session. Please contact your administrator if you need access.',
            ]);
        }

        // Login user
        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }




    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
