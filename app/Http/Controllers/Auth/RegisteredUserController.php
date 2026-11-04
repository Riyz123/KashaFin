<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * registro-simple branch: self-registration is instant here — no code
 * emailed to confirm the inbox, no dean/admin approval queue. Any
 * @upn.pe address the student types is trusted as-is and the account is
 * born active and approved, same as one a dean creates directly. This is
 * deliberately a SEPARATE branch from main, which keeps the full
 * verify-then-approve flow (RegistrationVerificationController,
 * NewPendingStudentNotification, etc.) — don't port this simplification
 * back to main.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', ['faculties' => Faculty::query()->orderBy('name')->get()]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Real UPN emails use the student's code as the local part (e.g.
        // N00321428@upn.pe), which isn't lowercase — normalize instead of
        // rejecting it, same as every other email address in practice.
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255', 'unique:'.User::class,
                'ends_with:@upn.pe',
            ],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'email.ends_with' => 'Debes registrarte con tu correo institucional (@upn.pe).',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'faculty_id' => $data['faculty_id'],
            'role' => 'estudiante',
            'is_active' => true,
            'approved_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
