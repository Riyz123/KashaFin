<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
     * panel — and only for @upn.edu.pe addresses. The account is created as
     * a *pending request*: no usable password yet (a random one nobody
     * knows), approved_at left null. It stays unusable until the student's
     * dean approves it (UserController::approve), which generates and
     * emails a real temporary password — that's the actual proof the
     * student controls that inbox, so there's no separate email-verification
     * step on top of it.
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
            'faculty_id' => ['required', 'exists:faculties,id'],
        ], [
            'email.ends_with' => 'Debes registrarte con tu correo institucional (@upn.edu.pe).',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(40)),
            'faculty_id' => $request->faculty_id,
        ]);

        return redirect()->route('login')->with('status',
            'Tu solicitud fue enviada. Tu decano la revisará y te enviaremos tu contraseña por correo cuando sea aprobada.'
        );
    }
}
