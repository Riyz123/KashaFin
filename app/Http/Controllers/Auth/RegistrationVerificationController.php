<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrationVerificationController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public const VERIFIED_TTL_MINUTES = 15;

    public function show(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('registration_email');

        if (! $email || ! Cache::has(RegisteredUserController::cacheKey($email))) {
            return redirect()->route('register')->withErrors([
                'email' => 'Tu código expiró o no encontramos una solicitud en curso. Regístrate de nuevo.',
            ]);
        }

        return view('auth.verify-registration', ['email' => $this->maskEmail($email)]);
    }

    /**
     * Only once the code matches is the inbox considered proven — the
     * account still isn't created here. Instead the verified name/faculty
     * move to their own short-lived cache entry and the student is sent to
     * set their own password (see showPassword()/store()); that's the
     * actual account-creation step now, with no dean-approval wait after it.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $email = $request->session()->get('registration_email');
        $key = $email ? RegisteredUserController::cacheKey($email) : null;
        $pending = $key ? Cache::get($key) : null;

        if (! $email || ! $pending) {
            return redirect()->route('register')->withErrors([
                'email' => 'Tu código expiró o no encontramos una solicitud en curso. Regístrate de nuevo.',
            ]);
        }

        if ($request->input('code') !== $pending['code']) {
            $pending['attempts']++;

            if ($pending['attempts'] >= self::MAX_ATTEMPTS) {
                Cache::forget($key);
                $request->session()->forget('registration_email');

                return redirect()->route('register')->withErrors([
                    'email' => 'Demasiados intentos fallidos. Regístrate de nuevo para recibir un código nuevo.',
                ]);
            }

            Cache::put($key, $pending, now()->addMinutes(RegisteredUserController::CODE_TTL_MINUTES));

            return back()->withErrors(['code' => 'Código incorrecto. Te quedan '.(self::MAX_ATTEMPTS - $pending['attempts']).' intento(s).']);
        }

        Cache::put(self::verifiedKey($email), [
            'name' => $pending['name'],
            'faculty_id' => $pending['faculty_id'],
        ], now()->addMinutes(self::VERIFIED_TTL_MINUTES));

        Cache::forget($key);

        return redirect()->route('register.password');
    }

    /**
     * Shows the "create your password" form — only reachable once the code
     * from verify() above actually matched.
     */
    public function showPassword(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('registration_email');

        if (! $email || ! Cache::has(self::verifiedKey($email))) {
            return redirect()->route('register')->withErrors([
                'email' => 'Tu verificación expiró. Regístrate de nuevo.',
            ]);
        }

        return view('auth.register-password', ['email' => $email]);
    }

    /**
     * Final step: the student picks their own password and the account is
     * created already active — no random temporary password, no dean
     * approval wait. Proving control of the @upn.pe inbox in verify() above
     * is what used to be the dean's job of confirming this is a real student.
     */
    public function store(Request $request): RedirectResponse
    {
        $email = $request->session()->get('registration_email');
        $verified = $email ? Cache::get(self::verifiedKey($email)) : null;

        if (! $email || ! $verified) {
            return redirect()->route('register')->withErrors([
                'email' => 'Tu verificación expiró. Regístrate de nuevo.',
            ]);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $student = User::create([
            'name' => $verified['name'],
            'email' => $email,
            'password' => Hash::make($data['password']),
            'role' => 'estudiante',
            'is_active' => true,
            'faculty_id' => $verified['faculty_id'],
        ]);

        $student->forceFill(['approved_at' => now()])->save();

        Cache::forget(self::verifiedKey($email));
        $request->session()->forget('registration_email');

        event(new Registered($student));

        Auth::login($student);

        return redirect()->route('dashboard');
    }

    public static function verifiedKey(string $email): string
    {
        return 'registration_verified:'.$email;
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('registration_email');
        $key = $email ? RegisteredUserController::cacheKey($email) : null;
        $pending = $key ? Cache::get($key) : null;

        if (! $email || ! $pending) {
            return redirect()->route('register')->withErrors([
                'email' => 'Tu código expiró o no encontramos una solicitud en curso. Regístrate de nuevo.',
            ]);
        }

        $pending['code'] = (string) random_int(100000, 999999);
        $pending['attempts'] = 0;

        Cache::put($key, $pending, now()->addMinutes(RegisteredUserController::CODE_TTL_MINUTES));

        Mail::to($email)->send(new RegistrationCodeMail($pending['name'], $pending['code']));

        return back()->with('status', 'Te enviamos un nuevo código.');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);

        $visible = Str::substr($local, 0, 1);

        return $visible.str_repeat('*', max(strlen($local) - 1, 1)).'@'.$domain;
    }
}
