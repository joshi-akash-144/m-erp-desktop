<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorController extends Controller
{
    private TwoFactorAuthenticationProvider $provider;

    public function __construct(TwoFactorAuthenticationProvider $provider)
    {
        $this->provider = $provider;
    }

    public function index(Request $request)
    {
        $user        = Auth::user();
        $qrCodeSvg   = null;
        $secretKey   = null;
        $recoveryCodes = null;
        $isConfirmed = $user->two_factor_confirmed_at !== null;
        $isPending   = $user->two_factor_secret !== null && ! $isConfirmed;

        if ($isPending) {
            $secretKey = decrypt($user->two_factor_secret);
            $qrCodeSvg = $this->buildQrSvg(
                $this->provider->qrCodeUrl(config('app.name'), $user->email, $secretKey)
            );
        }

        if ($isConfirmed) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        }

        return view('company-selector.pages.user.two-factor', compact(
            'user', 'qrCodeSvg', 'secretKey', 'recoveryCodes', 'isConfirmed', 'isPending'
        ));
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->two_factor_confirmed_at !== null) {
            return back()->with('info', 'Two-factor authentication is already enabled.');
        }

        $secret = $this->provider->generateSecretKey();

        $user->forceFill([
            'two_factor_secret'          => encrypt($secret),
            'two_factor_recovery_codes'  => null,
            'two_factor_confirmed_at'    => null,
        ])->save();

        return redirect()->route('profiles.two-factor.index')
            ->with('status', 'Scan the QR code below with your authenticator app, then enter the 6-digit code to confirm.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $user = Auth::user();

        if (! $user->two_factor_secret) {
            return back()->withErrors(['code' => 'Please enable two-factor authentication first.']);
        }

        $valid = $this->provider->verify(
            decrypt($user->two_factor_secret),
            $request->input('code')
        );

        if (! $valid) {
            return back()->withErrors(['code' => 'The code you entered is incorrect. Please try again.']);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at'   => now(),
        ])->save();

        return redirect()->route('profiles.two-factor.index')
            ->with('success', 'Two-factor authentication has been enabled. Save your recovery codes in a safe place.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        return redirect()->route('profiles.two-factor.index')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->two_factor_confirmed_at) {
            return back()->withErrors(['error' => 'Two-factor authentication is not enabled.']);
        }

        $codes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();

        return redirect()->route('profiles.two-factor.index')
            ->with('success', 'Recovery codes have been regenerated. Save the new codes now — the old ones are no longer valid.');
    }

    private function generateRecoveryCodes(): array
    {
        return Collection::times(8, fn () => Str::random(10) . '-' . Str::random(10))->all();
    }

    private function buildQrSvg(string $url): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            )
        ))->writeString($url);

        // Strip the XML declaration line
        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
