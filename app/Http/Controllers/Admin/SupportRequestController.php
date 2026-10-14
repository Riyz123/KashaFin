<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\SupportRequest;
use Illuminate\Http\Request;

class SupportRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = SupportRequest::query()
            ->visibleTo($request->user())
            ->with(['user', 'attendedBy', 'chatMessages'])
            ->orderByRaw("status = 'en_atencion' desc")
            ->orderByRaw("status = 'pendiente' desc")
            ->latest()
            ->paginate(15);

        return view('admin.support.index', ['requests' => $requests]);
    }

    public function attend(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($request->user()->canManage($supportRequest->user), 403, 'No tienes permisos sobre esta solicitud.');

        $supportRequest->markAttended($request->user());

        return back()->with('status', 'Solicitud marcada como atendida.');
    }

    /**
     * Only "asesor" requests go live — an "incidencia" report has nowhere
     * to reply to (it's not tied to the chat widget), it only ever gets
     * the plain attend() above.
     */
    public function startLive(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($request->user()->canManage($supportRequest->user), 403, 'No tienes permisos sobre esta solicitud.');
        abort_unless($supportRequest->type === 'asesor', 422, 'Solo se puede atender en vivo una solicitud de hablar con un asesor.');
        abort_unless($supportRequest->isPending(), 422, 'Esta solicitud ya fue procesada.');

        $supportRequest->startLiveAttention($request->user());

        return back()->with('status', 'Atención en vivo iniciada — ya puedes responderle dentro de su mismo chat.');
    }

    public function reply(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($request->user()->canManage($supportRequest->user), 403, 'No tienes permisos sobre esta solicitud.');
        abort_unless($supportRequest->isBeingAttendedLive(), 422, 'Esta solicitud no está en atención en vivo.');

        $data = $request->validate(['content' => ['required', 'string', 'max:1000']]);

        ChatMessage::create([
            'user_id' => $supportRequest->user_id,
            'role' => 'advisor',
            'content' => $data['content'],
            'support_request_id' => $supportRequest->id,
        ]);

        return back()->with('status', 'Mensaje enviado.');
    }

    /**
     * Polled from the expanded thread every ~5s to pick up the student's
     * new messages without the admin needing to resend their own reply
     * (which already refreshes the whole thread via the normal page
     * redirect) just to see them.
     */
    public function messages(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($request->user()->canManage($supportRequest->user), 403);

        $afterId = (int) $request->query('after_id', 0);

        $messages = $supportRequest->chatMessages()
            ->where('role', 'user')
            ->where('id', '>', $afterId)
            ->get(['id', 'role', 'content']);

        return response()->json(['messages' => $messages]);
    }

    public function finalize(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($request->user()->canManage($supportRequest->user), 403, 'No tienes permisos sobre esta solicitud.');
        abort_unless($supportRequest->isBeingAttendedLive(), 422, 'Esta solicitud no está en atención en vivo.');

        $supportRequest->markAttended($request->user());

        return back()->with('status', 'Atención finalizada.');
    }
}
