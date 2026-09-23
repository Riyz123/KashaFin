<?php

namespace App\Http\Controllers;

use App\Services\LiquidityProjectionService;
use Illuminate\Http\Request;

class ProjectionController extends Controller
{
    public function index(Request $request, LiquidityProjectionService $projection)
    {
        $user = $request->user();
        $horizon = (int) $request->query('horizon', 15);
        $horizon = in_array($horizon, [7, 15], true) ? $horizon : 15;

        $series = $projection->dailySeries($user, $horizon);
        $lowest = $projection->lowestPoint($series);

        return view('projections.index', [
            'series' => $series,
            'lowest' => $lowest,
            'horizon' => $horizon,
            'currentBalance' => $projection->currentBalance($user),
        ]);
    }
}
