<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * First step of "forgot my password", mirroring self-registration's email
 * verification exactly: a 6-digit code emailed via Gmail SMTP (not a
 * clickable reset link — same codes-not-links pattern the rest of this app
 * already uses) that must be confirmed before a new password can be set.
 * See PasswordRecoveryVerificationController for the rest of the flow.
 */
class PasswordResetLinkController extends Controller
{
    public const CODE_TTL_MINUTES = 15;

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);

        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        // Deliberately the same redirect either way — confirming whether an
        // email has an account here would let anyone fish for which
        // addresses are registered. If it exists, the code actually sends;
        // if not, nothing happens but the student sees the same next step.
        if ($user) {
            $code = (string) random_int(100000, 999999);

            Cache::put(self::cacheKey($data['email']), [
                'code' => $code,
                'attempts' => 0,
            ], now()->addMinutes(self::CODE_TTL_MINUTES));

            Mail::to($data['email'])->send(new PasswordResetCodeMail($user->name, $code));
        }

        $request->session()->put('password_reset_email', $data['email']);

        return redirect()->route('password.verify');
    }

    public static function cacheKey(string $email): string
    {
        return 'password_reset_code:'.$email;
    }
}
