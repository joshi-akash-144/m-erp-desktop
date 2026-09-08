<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LockScreenController extends Controller
{
    public function showLock(Request $request)
    {
        if (!session()->has('last_url')) {
            session(['last_url' => url()->previous()]);
        }

        session(['screen_locked' => true]);

        return view('auth.lock');
    }
    

    public function unlock(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = current_user();

        if (!Hash::check($request->password, $user->password)) {
            return redirect()
                ->route('lock.screen')
                ->withErrors(['password' => 'Incorrect password. Please try again.']);
        }


        session()->forget('screen_locked');

        return redirect(session('last_url', route('login')));
    }
    
}
