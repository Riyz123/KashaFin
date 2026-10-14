<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\SupportRequestEscalatedNotification;
use Illuminate\Http\Request;

class SupportRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = SupportRequest::query()
            ->visibleTo($request->user())
            ->with(['user.faculty', 'attendedBy', 'chatMessages'])
            ->orderByRaw("status = 'en_atencion' desc")
            ->orderByRaw("status = 'pendiente' desc")
            ->latest()
            ->paginate(15);

        return view('admin.support.index', ['requests' => $requests]);
    }

    public function attend(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($supportRequest->canBeManagedBy($request->user()), 403, 'No tienes permisos sobre esta solicitud.');

        $supportRequest->markAttended($request->user());

        return back()->with('status', 'Solicitud marcada como atendida.');
    }

    /**
     * Both "asesor" and "incidencia" requests can go live — the thread
     * lives in the student's own chat widget regardless of how the
     * request originated, seeded with whichever message they first sent
     * (the chat trigger phrase, or the incident report text).
     */
    public function startLive(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($supportRequest->canBeManagedBy($request->user()), 403, 'No tienes permisos sobre esta solicitud.');
        abort_unless($supportRequest->isPending(), 422, 'Esta solicitud ya fue procesada.');

        $supportRequest->startLiveAttention($request->user());

        return back()->with('status', 'Atención en vivo iniciada — ya puedes responderle dentro de su mismo chat.');
    }

    public function reply(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($supportRequest->canBeManagedBy($request->user()), 403, 'No tienes permisos sobre esta solicitud.');
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
        abort_unless($supportRequest->canBeManagedBy($request->user()), 403);

        $afterId = (int) $request->query('after_id', 0);

        $messages = $supportRequest->chatMessages()
            ->where('role', 'user')
            ->where('id', '>', $afterId)
            ->get(['id', 'role', 'content']);

        return response()->json(['messages' => $messages]);
    }

    /**
     * Polled from the list every ~5s so that if a second staff member
     * (another admin, or the student's dean) has the same list open, they
     * see a status change — someone started/finished attending, or
     * escalated it — without needing to manually reload.
     */
    public function statuses(Request $request)
    {
        $ids = array_filter(explode(',', (string) $request->query('ids', '')));

        $requests = SupportRequest::query()
            ->visibleTo($request->user())
            ->whereIn('id', $ids)
            ->with('attendedBy')
            ->get();

        return response()->json($requests->mapWithKeys(fn (SupportRequest $r) => [
            $r->id => ['status' => $r->status, 'attended_by' => $r->attendedBy?->name],
        ]));
    }

    public function finalize(Request $request, SupportRequest $supportRequest)
    {
        abort_unless($supportRequest->canBeManagedBy($request->user()), 403, 'No tienes permisos sobre esta solicitud.');
        abort_unless($supportRequest->isBeingAttendedLive(), 422, 'Esta solicitud no está en atención en vivo.');

        $supportRequest->markAttended($request->user());

        return back()->with('status', 'Atención finalizada.');
    }

    /**
     * A decano hands a case to administración (every admin), or an admin
     * hands it to the student's own decano — whichever applies to who's
     * escalating. Releases the request back to "pendiente" so the
     * receiving side picks it up fresh via the normal "Atender en vivo"/
     * "Marcar como atendido" flow.
     */
    public function escalate(Request $request, SupportRequest $supportRequest)
    {
        $actor = $request->user();

        abort_unless($supportRequest->canBeManagedBy($actor), 403, 'No tienes permisos sobre esta solicitud.');
        abort_if($supportRequest->isClosed(), 422, 'Esta solicitud ya fue atendida.');

        if ($actor->isDean()) {
            $recipients = User::query()->where('role', 'admin')->get();
        } else {
            $dean = $supportRequest->user->faculty?->dean();
            abort_unless($dean, 422, 'Esa facultad no tiene un decano asignado.');
            $recipients = collect([$dean]);
        }

        foreach ($recipients as $recipient) {
            $recipient->notify(new SupportRequestEscalatedNotification($supportRequest, $actor));
        }

        $supportRequest->releaseBackToPending();

        return back()->with('status', 'Solicitud derivada.');
    }
}
