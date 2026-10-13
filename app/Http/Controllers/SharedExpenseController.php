<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSharedExpenseRequest;
use App\Models\Category;
use App\Models\Expense;
use App\Models\SharedExpense;
use App\Models\SharedExpenseParticipant;
use App\Models\User;
use App\Notifications\SharedExpenseInviteNotification;
use Illuminate\Http\Request;

class SharedExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $sort = in_array($request->query('sort'), ['date', 'total_amount'], true) ? $request->query('sort') : 'date';
        $dir = $request->filled('sort') && $request->query('dir') !== 'desc' ? 'asc' : 'desc';

        $sharedExpenses = $user->sharedExpenses()
            ->with(['participants.user', 'participants.contributions', 'category'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('description', 'like', '%'.$request->string('search').'%');
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->whereDate('date', '>=', $request->date('from'));
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->whereDate('date', '<=', $request->date('to'));
            })
            ->orderBy($sort, $dir)
            ->get();

        $totalOwedToMe = $sharedExpenses->sum(fn (SharedExpense $shared) => $shared->totalOwedPending());

        $participations = $user->sharedExpenseParticipations()
            ->with(['sharedExpense.user', 'contributions'])
            ->latest()
            ->get();

        return view('shared-expenses.index', [
            'sharedExpenses' => $sharedExpenses,
            'totalOwedToMe' => $totalOwedToMe,
            'pendingInvitations' => $participations->where('status', 'pendiente')->values(),
            'myCommitments' => $participations->where('status', 'aceptado')->values(),
            'search' => $request->string('search')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
        ]);
    }

    public function create(Request $request)
    {
        $categories = Category::query()->forUser($request->user())->orderBy('name')->get();

        return view('shared-expenses.create', ['categories' => $categories]);
    }

    /**
     * Small JSON search used by the participant picker in the create form
     * — only real KashaFin accounts can be added, never a free-text name,
     * since this is meant for UPN students (and staff, once they're a
     * role) splitting a real expense with each other.
     */
    public function searchUsers(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $matches = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where('role', '!=', 'admin')
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('email', 'like', "%{$query}%"))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'email']);

        return response()->json($matches);
    }

    /**
     * Only `my_share` becomes a real Expense (the student's own spending);
     * the rest is tracked as money owed back by each invited participant,
     * never counted twice. Participants start as "pendiente" — they only
     * count toward anything once they accept.
     */
    public function store(StoreSharedExpenseRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $myShare = (float) ($data['my_share'] ?? 0);

        $expense = null;

        if ($myShare > 0) {
            $expense = Expense::create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'] ?? null,
                'amount' => $myShare,
                'date' => $data['date'],
                'description' => $data['description'].' (mi parte)',
            ]);
        }

        $sharedExpense = SharedExpense::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'expense_id' => $expense?->id,
            'description' => $data['description'],
            'total_amount' => $data['total_amount'],
            'my_share' => $myShare,
            'date' => $data['date'],
        ]);

        foreach ($data['participants'] as $participantData) {
            $invitedUser = User::find($participantData['user_id']);

            $participant = $sharedExpense->participants()->create([
                'user_id' => $invitedUser->id,
                'name' => $invitedUser->name,
                'amount_owed' => $participantData['amount_owed'],
                'daily_commitment' => $participantData['daily_commitment'] ?? null,
                'status' => 'pendiente',
            ]);

            $invitedUser->notify(new SharedExpenseInviteNotification($participant));
        }

        return redirect()->route('shared-expenses.index')->with('status', 'Gasto compartido registrado. Se notificó a cada participante.');
    }

    public function destroy(SharedExpense $sharedExpense)
    {
        $this->authorizeOwnership($sharedExpense);

        $sharedExpense->expense?->delete();
        $sharedExpense->delete();

        return redirect()->route('shared-expenses.index')->with('status', 'Gasto compartido eliminado.');
    }

    /**
     * The invited user accepts — only now does their share count toward
     * the shared expense. They can optionally commit to a daily rate here;
     * leaving it blank just means "I'll pay whenever, in any amounts".
     */
    public function accept(Request $request, SharedExpenseParticipant $participant)
    {
        abort_unless($participant->user_id === $request->user()->id, 403);
        abort_unless($participant->isPending(), 422, 'Esta invitación ya fue respondida.');

        $data = $request->validate([
            'daily_commitment' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $participant->accept($data['daily_commitment'] ?? null);

        return back()->with('status', 'Invitación aceptada.');
    }

    public function reject(Request $request, SharedExpenseParticipant $participant)
    {
        abort_unless($participant->user_id === $request->user()->id, 403);
        abort_unless($participant->isPending(), 422, 'Esta invitación ya fue respondida.');

        $participant->reject();

        return back()->with('status', 'Invitación rechazada.');
    }

    /**
     * The participant logs their own payment toward their share — same
     * self-reported pattern as the rest of the app's money entry.
     */
    public function storeContribution(Request $request, SharedExpenseParticipant $participant)
    {
        abort_unless($participant->user_id === $request->user()->id, 403);
        abort_unless($participant->isAccepted(), 422, 'Primero debes aceptar esta invitación.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $participant->contributions()->create($data);

        return back()->with('status', 'Pago registrado.');
    }

    private function authorizeOwnership(SharedExpense $sharedExpense): void
    {
        abort_unless($sharedExpense->user_id === auth()->id(), 403);
    }
}
