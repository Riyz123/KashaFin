<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\Ai\BudgetChatIntent;
use App\Services\Ai\ChatService;
use App\Services\Ai\ExpenseChatIntent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function send(
        Request $request,
        ExpenseChatIntent $expenseIntent,
        BudgetChatIntent $budgetIntent,
        ChatService $chat
    ) {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $message = trim($data['message']);

        $intentReply = $expenseIntent->handle($user, $message) ?? $budgetIntent->handle($user, $message);

        if ($intentReply !== null) {
            ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'content' => $message]);
            ChatMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => $intentReply]);

            return response()->json(['reply' => $intentReply]);
        }

        $reply = $chat->reply($user, $message);

        return response()->json(['reply' => $reply]);
    }
}
