<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class PasswordRecoveryVerificationController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-password-reset', ['email' => $this->maskEmail($email)]);
    }

    /**
     * Same shape as registration's verify(): never reveals whether the
     * account actually exists, whether the code is simply wrong, or
     * whether no code was ever sent for this email — all three look like
     * "código incorrecto" to the student, which is deliberate (see
     * PasswordResetLinkController::store()'s comment on the same thing).
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $key = PasswordResetLinkController::cacheKey($email);
        $pending = Cache::get($key);

        if (! $pending || $request->input('code') !== $pending['code']) {
            if ($pending) {
                $pending['attempts']++;

                if ($pending['attempts'] >= self::MAX_ATTEMPTS) {
                    Cache::forget($key);
                    $request->session()->forget('password_reset_email');

                    return redirect()->route('password.request')->withErrors([
                        'email' => 'Demasiados intentos fallidos. Solicita un código nuevo.',
                    ]);
                }

                Cache::put($key, $pending, now()->addMinutes(PasswordResetLinkController::CODE_TTL_MINUTES));
            }

            return back()->withErrors(['code' => 'Código incorrecto.']);
        }

        Cache::forget($key);
        $request->session()->put('password_reset_verified', true);

        return redirect()->route('password.reset');
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            Cache::put(PasswordResetLinkController::cacheKey($email), [
                'code' => $code,
                'attempts' => 0,
            ], now()->addMinutes(PasswordResetLinkController::CODE_TTL_MINUTES));

            Mail::to($email)->send(new PasswordResetCodeMail($user->name, $code));
        }

        return back()->with('status', 'Si esa cuenta existe, te enviamos un nuevo código.');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $this->hasVerifiedCode($request)) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    /**
     * The actual password change — only reachable after verify() set the
     * session flag, same spirit as ForcePasswordController but for a
     * student who locked themselves out instead of a first login.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $this->hasVerifiedCode($request)) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = $request->session()->get('password_reset_email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'must_change_password' => false,
            ])->save();
        }

        $request->session()->forget(['password_reset_email', 'password_reset_verified']);

        return redirect()->route('login')->with('status', 'Tu contraseña se actualizó. Ya puedes iniciar sesión con ella.');
    }

    private function hasVerifiedCode(Request $request): bool
    {
        return $request->session()->get('password_reset_email')
            && $request->session()->get('password_reset_verified') === true;
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);

        $visible = Str::substr($local, 0, 1);

        return $visible.str_repeat('*', max(strlen($local) - 1, 1)).'@'.$domain;
    }
}
