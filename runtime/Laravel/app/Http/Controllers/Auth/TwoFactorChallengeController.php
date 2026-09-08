<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        $code         = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        if ($code) {
            $provider = app(TwoFactorAuthenticationProvider::class);
            $valid    = $provider->verify(decrypt($user->two_factor_secret), $code);

            if (! $valid) {
                return back()->withErrors(['code' => 'The provided two-factor code is invalid.']);
            }
        } elseif ($recoveryCode) {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

            if (! in_array($recoveryCode, $codes)) {
                return back()->withErrors(['recovery_code' => 'The provided recovery code is invalid.']);
            }

            // Invalidate the used recovery code
            $remaining = array_values(array_filter($codes, fn ($c) => $c !== $recoveryCode));
            $user->forceFill([
                'two_factor_recovery_codes' => encrypt(json_encode($remaining)),
            ])->save();
        } else {
            return back()->withErrors(['code' => 'Please enter a two-factor code or a recovery code.']);
        }

        $remember = $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');
        $request->session()->regenerate();

        Auth::login($user, $remember);

        UserLoginSession::create([
            'user_id'    => $user->id,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'login_at'   => now(),
        ]);

        return redirect()->intended(route('company-selection.index'));
    }
}
