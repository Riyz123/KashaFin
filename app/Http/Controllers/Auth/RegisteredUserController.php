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
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
     * Handle an incoming registration request. Self-registration is only
     * for students — admin and dean accounts are created from the admin
     * panel — and only for @upn.edu.pe addresses, so only real UPN students
     * can create one. The "EnsureEmailIsVerified" middleware then blocks
     * access until they confirm the address via the email Breeze sends.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class,
                'ends_with:@upn.edu.pe',
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'faculty_id' => ['required', 'exists:faculties,id'],
        ], [
            'email.ends_with' => 'Debes registrarte con tu correo institucional (@upn.edu.pe).',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'faculty_id' => $request->faculty_id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
