<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class ForcePasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Contraseña actualizada. Ya puedes usar KashaFin.');
    }
}
