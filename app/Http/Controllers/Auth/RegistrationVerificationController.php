<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationCodeMail;
use App\Models\User;
use App\Services\TemporaryPasswordService;
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

    public function __construct(private TemporaryPasswordService $temporaryPasswords) {}

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
     * Once the code matches, the inbox is proven — the account is created
     * right here (no dean-approval wait: proving control of the @upn.pe
     * inbox is what that approval used to stand in for), with a
     * system-generated temporary password emailed as a fallback. The
     * student is then sent to set their own password (store() below); if
     * they never finish that, the temp password still gets them in, and
     * EnsurePasswordIsFresh forces the same "set a real password" screen on
     * that first login.
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

        $student = User::create([
            'name' => $pending['name'],
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'role' => 'estudiante',
            'faculty_id' => $pending['faculty_id'],
        ]);

        $this->temporaryPasswords->grant($student);

        event(new Registered($student));

        Cache::forget($key);
        $request->session()->put('registration_user_id', $student->id);

        return redirect()->route('register.password');
    }

    /**
     * Shows the "create your password" form — only reachable for the
     * account verify() just created, and only until it either gets its own
     * password here or the student logs in with the temporary one instead
     * (must_change_password flips to false either way).
     */
    public function showPassword(Request $request): View|RedirectResponse
    {
        $student = $this->pendingPasswordUser($request);

        if (! $student) {
            return redirect()->route('register')->withErrors([
                'email' => 'Tu verificación expiró. Regístrate de nuevo.',
            ]);
        }

        return view('auth.register-password', ['email' => $student->email]);
    }

    /**
     * Final step: the student picks their own password in place of the
     * temporary one that was already emailed to them.
     */
    public function store(Request $request): RedirectResponse
    {
        $student = $this->pendingPasswordUser($request);

        if (! $student) {
            return redirect()->route('register')->withErrors([
                'email' => 'Tu verificación expiró. Regístrate de nuevo.',
            ]);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $student->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        $request->session()->forget(['registration_user_id', 'registration_email']);

        Auth::login($student);

        return redirect()->route('dashboard');
    }

    /**
     * must_change_password is the actual guard, not just the session key's
     * presence — once the student logs in with the emailed temporary
     * password instead of coming back here, EnsurePasswordIsFresh already
     * sent them to password.force-edit, so this step has nothing left to do.
     */
    private function pendingPasswordUser(Request $request): ?User
    {
        $userId = $request->session()->get('registration_user_id');

        if (! $userId) {
            return null;
        }

        $student = User::find($userId);

        return $student?->must_change_password ? $student : null;
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
