<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\View\Composers\ChatWidgetComposer;
use App\View\Composers\LayoutComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        View::composer(['layouts.app', 'layouts.admin'], LayoutComposer::class);
        View::composer('components.chat-widget', ChatWidgetComposer::class);

        // Applies everywhere a password gets set via Password::defaults()
        // (self-registration, forced change, reset, profile update, dean
        // creation) — matches the live checklist shown by the
        // x-password-requirements component so the server never rejects
        // something the UI didn't already warn about.
        Password::defaults(fn () => Password::min(6)->mixedCase()->symbols());
    }
}
