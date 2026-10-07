<?php

namespace App\View\Composers;

use App\Models\ChatMessage;
use Illuminate\View\View;

class ChatWidgetComposer
{
    private const HISTORY_LIMIT = 20;

    public function compose(View $view): void
    {
        $user = auth()->user();

        $messages = $user
            ? ChatMessage::query()->forUser($user)->latest('id')->take(self::HISTORY_LIMIT)->get()->reverse()->values()
            : collect();

        $view->with('recentChatMessages', $messages);
    }
}
