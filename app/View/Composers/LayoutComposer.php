<?php

namespace App\View\Composers;

use Illuminate\View\View;

class LayoutComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        $view->with('userSettings', $user?->settings);
    }
}
