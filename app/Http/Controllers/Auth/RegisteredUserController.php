<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationCodeMail;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public const CODE_TTL_MINUTES = 15;

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', ['faculties' => Faculty::query()->orderBy('name')->get()]);
    }

    /**
     * First step of self-registration: validates the data and emails a
     * 6-digit code to confirm the student actually controls that inbox —
     * the account itself isn't created yet (see
     * RegistrationVerificationController::verify()), so a typo'd or fake
     * email never reaches the dean's pending queue.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class,
                'ends_with:@upn.edu.pe',
            ],
            'faculty_id' => ['required', 'exists:faculties,id'],
        ], [
            'email.ends_with' => 'Debes registrarte con tu correo institucional (@upn.edu.pe).',
        ]);

        $code = (string) random_int(100000, 999999);

        Cache::put(self::cacheKey($data['email']), [
            'name' => $data['name'],
            'faculty_id' => $data['faculty_id'],
            'code' => $code,
            'attempts' => 0,
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        Mail::to($data['email'])->send(new RegistrationCodeMail($data['name'], $code));

        $request->session()->put('registration_email', $data['email']);

        return redirect()->route('register.verify');
    }

    public static function cacheKey(string $email): string
    {
        return 'registration_code:'.$email;
    }
}
