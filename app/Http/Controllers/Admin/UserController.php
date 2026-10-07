<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
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
            ->when($request->string('status') === 'active', fn ($query) => $query->where('is_active', true)->whereNotNull('approved_at'))
            ->when($request->string('status') === 'inactive', fn ($query) => $query->where('is_active', false)->whereNotNull('approved_at'))
            ->when($request->string('status') === 'pending', fn ($query) => $query->whereNull('approved_at'))
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
        abort_if($user->isPendingApproval(), 422, 'Esta solicitud todavía no fue aprobada.');

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('status', $user->is_active ? 'Cuenta activada correctamente.' : 'Cuenta desactivada correctamente.');
    }

    /**
     * Approves a self-registered student's pending request: generates a
     * real password (the random one set at registration is unknown to
     * everyone, including the student) and emails it to them.
     */
    public function approve(Request $request, User $user)
    {
        abort_unless($request->user()->canManage($user), 403, 'No tienes permisos para aprobar esta cuenta.');
        abort_unless($user->isPendingApproval(), 422, 'Esta solicitud ya fue procesada.');

        $this->grantAccess($user);

        return back()->with('status', "Solicitud aprobada. Se le envió la contraseña a {$user->email}.");
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
                    $student->isPendingApproval() ? 'Pendiente' : ($student->is_active ? 'Activo' : 'Desactivado'),
                    $student->created_at->format('d/m/Y'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function importForm(Request $request): View
    {
        $actor = $request->user();

        return view('admin.users.import', [
            'faculties' => $actor->isAdmin() ? Faculty::query()->orderBy('name')->get() : null,
        ]);
    }

    /**
     * Bulk-creates already-approved student accounts from a CSV roster
     * (columns: nombre, correo). Each row gets its own random password,
     * emailed individually — same mechanism as a single approval, just
     * looped. Invalid/duplicate rows are skipped and reported, never
     * silently dropped.
     */
    public function import(Request $request)
    {
        $actor = $request->user();

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'faculty_id' => [$actor->isAdmin() ? 'required' : 'nullable', 'exists:faculties,id'],
        ]);

        $facultyId = $actor->isAdmin() ? (int) $data['faculty_id'] : $actor->faculty_id;

        $handle = fopen($data['file']->getRealPath(), 'r');
        $header = array_map(fn ($value) => Str::lower(trim($value)), fgetcsv($handle) ?: []);
        $nameIndex = array_search('nombre', $header, true);
        $emailIndex = array_search('correo', $header, true);

        if ($nameIndex === false || $emailIndex === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'El CSV debe tener las columnas "nombre" y "correo".']);
        }

        $created = 0;
        $skipped = [];

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim($row[$nameIndex] ?? '');
            $email = Str::lower(trim($row[$emailIndex] ?? ''));

            if ($name === '' || $email === '') {
                continue;
            }

            if (! str_ends_with($email, '@upn.edu.pe')) {
                $skipped[] = "{$email} (no es @upn.edu.pe)";

                continue;
            }

            if (User::where('email', $email)->exists()) {
                $skipped[] = "{$email} (ya existe)";

                continue;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'faculty_id' => $facultyId,
            ]);

            $this->grantAccess($user);
            $created++;
        }

        fclose($handle);

        $status = "{$created} cuenta(s) creada(s) y notificada(s) por correo.";

        if ($skipped) {
            $status .= ' Omitidas: '.implode(', ', $skipped);
        }

        return back()->with('status', $status);
    }

    /**
     * Shared by approve() and import(): generates and persists a real
     * password, marks the account approved/active, forces a password
     * change on first login, and emails the temporary password.
     */
    private function grantAccess(User $user): void
    {
        $temporaryPassword = Str::password(10, symbols: false);

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'approved_at' => now(),
            'is_active' => true,
            'must_change_password' => true,
        ])->save();

        Mail::to($user)->send(new TemporaryPasswordMail($user, $temporaryPassword));
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
