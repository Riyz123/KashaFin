<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\Request;

class SupportRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = SupportRequest::query()
            ->visibleTo($request->user())
            ->with('user', 'attendedBy')
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
}
