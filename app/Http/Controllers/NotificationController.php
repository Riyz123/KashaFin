<?php

namespace App\Http\Controllers;

use App\Services\LiquidityAlertService;
use App\Support\NotificationLink;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $alerts = $request->user()->liquidityAlerts()
            ->orderByDesc('sent_at')
            ->paginate(10, ['*'], 'alertas');

        $reminders = $request->user()->notifications()
            ->latest()
            ->paginate(10, ['*'], 'recordatorios');

        // Visiting this page is what "reading" them means — there's no
        // per-item read affordance, opening the list clears the badge.
        $reminders->getCollection()->markAsRead();

        return view('notifications.index', ['alerts' => $alerts, 'reminders' => $reminders]);
    }

    /**
     * Polled every ~20s from the sidebar (InfinityFree has no WebSockets,
     * so this is the closest thing to "live" available). Deliberately
     * lightweight: just the count and enough of each item to show a toast,
     * never the full paginated view.
     */
    public function unread(Request $request)
    {
        $unread = $request->user()->unreadNotifications()->latest()->take(10)->get();

        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
            'items' => $unread->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? 'Notificación',
                'url' => NotificationLink::for($n->data)['url'] ?? route('notifications.index'),
            ])->values(),
        ]);
    }

    public function sendNow(Request $request, LiquidityAlertService $service)
    {
        $alert = $service->checkAndNotify($request->user(), force: true);

        $message = $alert
            ? 'Se generó una alerta porque tu proyección está por debajo del umbral.'
            : 'Tu proyección actual está por encima del umbral configurado; no se generó ninguna alerta.';

        return back()->with('status', $message);
    }
}
