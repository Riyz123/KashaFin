<?php

namespace App\Http\Controllers;

use App\Services\LiquidityAlertService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $alerts = $request->user()->liquidityAlerts()
            ->orderByDesc('sent_at')
            ->paginate(10);

        return view('notifications.index', ['alerts' => $alerts]);
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
