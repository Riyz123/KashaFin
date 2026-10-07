<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\View\Composers\ChatWidgetComposer;
use App\View\Composers\LayoutComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
    }
}
