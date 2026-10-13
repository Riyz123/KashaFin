<?php

namespace App\Http\Controllers;

use App\Services\SupportRequestService;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $myRequests = $request->user()->supportRequests()->latest()->take(5)->get();

        return view('help.index', ['myRequests' => $myRequests]);
    }

    public function store(Request $request, SupportRequestService $support)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $support->create($request->user(), 'incidencia', $data['message']);

        return redirect()->route('help.index')->with('status', 'Incidencia reportada. Un asesor la revisará pronto.');
    }
}
