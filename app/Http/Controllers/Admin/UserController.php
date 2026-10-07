<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = $this->scopedStudents($request)
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

    public function toggleActive(Request $request, User $user)
    {
        abort_unless($request->user()->canManage($user), 403, 'No tienes permisos para modificar esta cuenta.');

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('status', $user->is_active ? 'Cuenta activada correctamente.' : 'Cuenta desactivada correctamente.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->canManage($user), 403, 'No tienes permisos para eliminar esta cuenta.');

        $user->delete();

        return back()->with('status', 'Cuenta eliminada correctamente, junto con todos sus datos.');
    }

    /**
     * CSV export for a dean's own faculty (or every student, for the master
     * admin) — "base de datos legible" of active/inactive students.
     */
    public function export(Request $request): StreamedResponse
    {
        $students = $this->scopedStudents($request)->orderBy('name')->get();

        $filename = 'estudiantes-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'Correo', 'Facultad', 'Estado', 'Registrado']);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->name,
                    $student->email,
                    $student->faculty?->name ?? '—',
                    $student->is_active ? 'Activo' : 'Desactivado',
                    $student->created_at->format('d/m/Y'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function scopedStudents(Request $request)
    {
        $actor = $request->user();

        return User::query()
            ->where('role', 'estudiante')
            ->when($actor->isDean(), fn ($query) => $query->where('faculty_id', $actor->faculty_id))
            ->with('faculty');
    }
}
