<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoalRequest;
use App\Models\SavingsGoal;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        $goals = $request->user()->savingsGoals()
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('goals.index', ['goals' => $goals]);
    }

    public function create()
    {
        return view('goals.create');
    }

    public function store(StoreGoalRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        SavingsGoal::create($data);

        return redirect()->route('goals.index')->with('status', 'Meta creada correctamente.');
    }

    public function edit(SavingsGoal $goal)
    {
        $this->authorizeOwnership($goal);

        return view('goals.edit', ['goal' => $goal]);
    }

    public function update(StoreGoalRequest $request, SavingsGoal $goal)
    {
        $this->authorizeOwnership($goal);

        $goal->update($request->validated());

        return redirect()->route('goals.index')->with('status', 'Meta actualizada correctamente.');
    }

    public function destroy(SavingsGoal $goal)
    {
        $this->authorizeOwnership($goal);

        $goal->delete();

        return redirect()->route('goals.index')->with('status', 'Meta eliminada correctamente.');
    }

    public function history(Request $request)
    {
        $goals = $request->user()->savingsGoals()
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->get();

        return view('goals.history', ['goals' => $goals]);
    }

    private function authorizeOwnership(SavingsGoal $goal): void
    {
        abort_unless($goal->user_id === auth()->id(), 403);
    }
}
