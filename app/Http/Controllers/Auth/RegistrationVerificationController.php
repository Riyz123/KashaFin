<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationCodeMail;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\NewPendingStudentNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationVerificationController extends Controller
{
    private const MAX_ATTEMPTS = 5;

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
     * Only once the code matches does the real pending-approval account get
     * created — same shape as before (unusable random password,
     * approved_at null, waiting on the dean) — this step just replaces
     * "typing an email" with "proving you control that inbox" before that
     * request ever reaches the dean's queue.
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
            'faculty_id' => $pending['faculty_id'],
        ]);

        $this->notifyApprovers($student);

        Cache::forget($key);
        $request->session()->forget('registration_email');

        return redirect()->route('login')->with('status',
            'Correo verificado. Tu solicitud fue enviada: tu decano la revisará y te enviaremos tu contraseña cuando sea aprobada.'
        );
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

    /**
     * The dean of that faculty (if one exists yet) plus every master admin
     * — whoever actually has the power to approve this student should
     * find out the moment the request lands, not whenever they next
     * happen to check the Usuarios page.
     */
    private function notifyApprovers(User $student): void
    {
        $approvers = User::where('role', 'admin')->get();

        if ($dean = Faculty::find($student->faculty_id)?->dean()) {
            $approvers->push($dean);
        }

        foreach ($approvers as $approver) {
            $approver->notify(new NewPendingStudentNotification($student));
        }
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);

        $visible = Str::substr($local, 0, 1);

        return $visible.str_repeat('*', max(strlen($local) - 1, 1)).'@'.$domain;
    }
}
