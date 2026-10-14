<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;

class LiveSupportController extends Controller
{
    /**
     * Polled by the chat widget every ~5s while this student has an active
     * live-support request. Only ever returns `advisor` messages — the
     * student's own messages are already shown client-side the moment they
     * send them, so returning those back would just duplicate them.
     */
    public function poll(Request $request)
    {
        $user = $request->user();
        $afterId = (int) $request->query('after_id', 0);
        $liveRequest = $user->activeLiveSupportRequest();

        $messages = $liveRequest
            ? ChatMessage::query()
                ->where('user_id', $user->id)
                ->where('role', 'advisor')
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->get(['id', 'content'])
            : collect();

        return response()->json([
            'live' => $liveRequest !== null,
            'messages' => $messages,
        ]);
    }
}
