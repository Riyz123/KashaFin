<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoalContributionRequest;
use App\Models\SavingsGoal;
use App\Services\LiquidityProjectionService;
use Illuminate\Support\Carbon;

class GoalContributionController extends Controller
{
    public function store(
        StoreGoalContributionRequest $request,
        SavingsGoal $goal,
        LiquidityProjectionService $projection
    ) {
        abort_unless($goal->user_id === auth()->id(), 403);

        $data = $request->validated();
        $amount = (float) $data['amount'];

        $lowest = $projection->lowestPoint($projection->sevenDayProjection($goal->user));
        $threshold = (float) $goal->user->settings->liquidity_threshold;
        $wouldDropBelowThreshold = ((float) $lowest['balance'] - $amount) < $threshold;

        if ($wouldDropBelowThreshold && ! $request->boolean('confirmed')) {
            return back()
                ->withInput()
                ->with('goalWarning', [
                    'goal_id' => $goal->id,
                    'message' => 'Apartar este monto haría que tu proyección de liquidez caiga por debajo de tu umbral configurado. Confirma si deseas continuar.',
                ]);
        }

        $goal->addContribution($amount, Carbon::parse($data['date']), $data['note'] ?? null);

        return redirect()->route('goals.index')->with('status', 'Aporte registrado correctamente.');
    }
}
