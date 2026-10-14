<?php

namespace App\Http\Controllers;

use App\Support\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $activities = ActivityLog::forCurrentUser($request->query('section'))
            ->paginate(20)
            ->withQueryString();

        return view('activity.index', [
            'activities' => $activities,
            'section' => $request->string('section')->toString(),
        ]);
    }
}
