<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->where('role', 'estudiante')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->string('status') === 'active');
            })
            ->withCount(['incomes', 'expenses'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
        ]);
    }

    public function toggleActive(User $user)
    {
        abort_if($user->isAdmin(), 403, 'No puedes modificar una cuenta de administrador.');

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('status', $user->is_active ? 'Cuenta activada correctamente.' : 'Cuenta desactivada correctamente.');
    }

    public function destroy(User $user)
    {
        abort_if($user->isAdmin(), 403, 'No puedes eliminar una cuenta de administrador.');

        $user->delete();

        return back()->with('status', 'Cuenta eliminada correctamente, junto con todos sus datos.');
    }
}
