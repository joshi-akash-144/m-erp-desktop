<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserLoginSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = auth()->user();

        // If the user has confirmed 2FA, step them back out and start the challenge flow
        if ($user->two_factor_confirmed_at !== null && $user->two_factor_secret !== null) {
            $userId   = $user->getAuthIdentifier();
            $remember = $request->boolean('remember');

            Auth::guard('web')->logout();

            $request->session()->put('login.id', $userId);
            $request->session()->put('login.remember', $remember);

            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();

        UserLoginSession::create([
            'user_id'    => $user->id,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'login_at'   => now(),
        ]);

        return redirect()->intended(route('company-selection.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $sessionId = $request->session()->getId();

        UserLoginSession::where('session_id', $sessionId)
            ->whereNull('logout_at')
            ->update(['logout_at' => now()]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
