<?php

namespace App\Services;

use App\Mail\TemporaryPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Shared by every path that activates a student account with a
 * system-generated password instead of one the student already chose:
 * admin/dean manual add, CSV import, resend, and self-registration's
 * fallback email sent right after the code is verified (so the account is
 * still reachable even if the student never finishes picking their own
 * password — EnsurePasswordIsFresh forces that screen on first login with it).
 */
class TemporaryPasswordService
{
    public function grant(User $user): string
    {
        $temporaryPassword = Str::password(10, symbols: false);

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'approved_at' => now(),
            'is_active' => true,
            'must_change_password' => true,
        ])->save();

        // Best-effort: if the mailer throws (connection refused, rejected,
        // whatever), the account is still fully usable — the caller always
        // has $temporaryPassword as a fallback way to hand it over another way.
        try {
            Mail::to($user)->send(new TemporaryPasswordMail($user, $temporaryPassword));
        } catch (\Throwable $e) {
            report($e);
        }

        return $temporaryPassword;
    }
}
