<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\Ai\AdvisorChatIntent;
use App\Services\Ai\BudgetChatIntent;
use App\Services\Ai\ChatService;
use App\Services\Ai\ExpenseChatIntent;
use App\Services\Ai\IncomeChatIntent;
use App\Services\Ai\LoanChatIntent;
use App\Services\Ai\SavingsGoalChatIntent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function send(
        Request $request,
        AdvisorChatIntent $advisorIntent,
        ExpenseChatIntent $expenseIntent,
        BudgetChatIntent $budgetIntent,
        IncomeChatIntent $incomeIntent,
        SavingsGoalChatIntent $goalIntent,
        LoanChatIntent $loanIntent,
        ChatService $chat
    ) {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $message = trim($data['message']);

        // Checked before anything else: while a human is replying live
        // (Admin → Soporte → "Atender en vivo"), the AI stays out of it
        // entirely for this student — just record the message and let the
        // widget's poll pick up the advisor's reply whenever it comes.
        if ($liveRequest = $user->activeLiveSupportRequest()) {
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'user',
                'content' => $message,
                'support_request_id' => $liveRequest->id,
            ]);

            return response()->json(['reply' => null, 'live' => true]);
        }

        // Checked first: if a "talk to an advisor" request is in progress,
        // this message is its answer (or a cancellation), not a new intent.
        $intentReply = $advisorIntent->handle($user, $message)
            ?? $expenseIntent->handle($user, $message)
            ?? $budgetIntent->handle($user, $message)
            ?? $incomeIntent->handle($user, $message)
            ?? $goalIntent->handle($user, $message)
            ?? $loanIntent->handle($user, $message);

        if ($intentReply !== null) {
            ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'content' => $message]);
            ChatMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => $intentReply]);

            return response()->json(['reply' => $intentReply]);
        }

        $reply = $chat->reply($user, $message);

        return response()->json(['reply' => $reply]);
    }
}
