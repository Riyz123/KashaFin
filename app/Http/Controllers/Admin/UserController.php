<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use App\Services\TemporaryPasswordService;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct(private TemporaryPasswordService $temporaryPasswords) {}

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
            'faculties' => $request->user()->isAdmin() ? Faculty::query()->orderBy('name')->get() : null,
        ]);
    }

    /**
     * Polled every ~20s from the Usuarios page so "en línea"/"última vez"
     * updates on its own — scoped the same way the list itself is (a dean
     * only ever gets presence for their own faculty's students, never
     * anyone else's), so this can't be used to probe arbitrary user ids.
     */
    public function presence(Request $request)
    {
        $ids = array_filter(explode(',', (string) $request->query('ids', '')));

        $users = $this->scopedStudents($request)->whereIn('id', $ids)->get(['id', 'last_seen_at']);

        return response()->json($users->mapWithKeys(fn (User $user) => [
            $user->id => ['online' => $user->isOnline(), 'label' => $user->presenceLabel()],
        ]));
    }

    /**
     * Manually create a student account (not self-registered, not from a
     * CSV) — already approved, same temporary-password-by-email mechanism
     * as the other two paths. A dean creates it in their own faculty; the
     * admin master picks any faculty.
     */
    public function store(Request $request)
    {
        $actor = $request->user();

        // Real UPN emails use the student's code as the local part (e.g.
        // N00321428@upn.pe), which isn't lowercase — normalize instead of
        // rejecting it.
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class, 'ends_with:@upn.pe'],
            'faculty_id' => [$actor->isAdmin() ? 'required' : 'nullable', 'exists:faculties,id'],
        ], [
            'email.ends_with' => 'Debe ser un correo institucional (@upn.pe).',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(40)),
            'faculty_id' => $actor->isAdmin() ? $data['faculty_id'] : $actor->faculty_id,
        ]);

        $temporaryPassword = $this->temporaryPasswords->grant($user);

        return back()->with('status', "Estudiante agregado. Se intentó enviar la contraseña a {$user->email} — por si el correo no llega, esta es su contraseña temporal (no vence, pero solo sirve para el primer ingreso): {$temporaryPassword}");
    }

    /**
     * Reassigning which faculty a student belongs to (or removing it
     * entirely) changes which dean manages them — admin-master-only, a
     * dean has no reason to move a student out of their own reach.
     */
    public function updateFaculty(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Solo el administrador puede reasignar la facultad de un estudiante.');

        $data = $request->validate([
            'faculty_id' => ['nullable', 'exists:faculties,id'],
        ]);

        $user->update(['faculty_id' => $data['faculty_id'] ?? null]);

        return back()->with('status', 'Facultad actualizada correctamente.');
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

        $temporaryPassword = $this->temporaryPasswords->grant($user);

        return back()->with('status', "Solicitud aprobada. Se intentó enviar la contraseña a {$user->email} — por si el correo no llega, esta es su contraseña temporal (no vence, pero solo sirve para el primer ingreso): {$temporaryPassword}");
    }

    /**
     * Approves every pending request visible to this actor in one go (a
     * dean's own faculty only, scopedStudents() already enforces that) —
     * for when there's a backlog and going one by one is tedious. Each one
     * still goes through TemporaryPasswordService individually, same as a
     * single approve(), so nothing about the per-student behavior changes.
     */
    public function approveAll(Request $request)
    {
        $pending = $this->scopedStudents($request)->whereNull('approved_at')->get();

        foreach ($pending as $student) {
            $this->temporaryPasswords->grant($student);
        }

        return back()->with('status', $pending->isEmpty()
            ? 'No había solicitudes pendientes.'
            : "Se aprobaron {$pending->count()} solicitud(es). Se intentó enviarle una contraseña a cada estudiante por correo."
        );
    }

    /**
     * For when the first temporary-password email never arrives (lost,
     * filtered as spam/quarantined, etc.) — generates and sends a brand new
     * one instead of leaving the account stuck. Only makes sense while the
     * student still hasn't logged in with a real password of their own.
     */
    public function resendPassword(Request $request, User $user)
    {
        abort_unless($request->user()->canManage($user), 403, 'No tienes permisos sobre esta cuenta.');
        abort_if($user->isPendingApproval(), 422, 'Esta solicitud todavía no fue aprobada.');
        abort_unless($user->must_change_password, 422, 'Este estudiante ya inició sesión con su propia contraseña.');

        $temporaryPassword = $this->temporaryPasswords->grant($user);

        return back()->with('status', "Se intentó enviar una nueva contraseña a {$user->email} — por si el correo no llega, esta es la contraseña temporal (no vence, pero solo sirve para el primer ingreso): {$temporaryPassword}");
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
            Csv::start($handle);
            Csv::row($handle, ['Nombre', 'Correo', 'Facultad', 'Estado', 'Registrado']);

            foreach ($students as $student) {
                Csv::row($handle, [
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
     * A fillable CSV template — "nombre,correo,facultad" with one example
     * row. When a specific faculty is passed, that column comes pre-filled
     * with its name on every row, so uploading it back doesn't need the
     * dropdown touched at all: import() below reads "facultad" straight off
     * each row when the column is present.
     */
    public function importTemplate(Request $request, ?Faculty $faculty = null): StreamedResponse
    {
        $actor = $request->user();

        if (! $actor->isAdmin()) {
            abort_if($faculty && $faculty->id !== $actor->faculty_id, 403);
            $faculty = $faculty ?? $actor->faculty;
        }

        $filename = $faculty
            ? 'plantilla-estudiantes-'.Str::slug($faculty->name).'.csv'
            : 'plantilla-estudiantes.csv';

        return response()->streamDownload(function () use ($faculty) {
            $handle = fopen('php://output', 'w');
            Csv::start($handle);
            Csv::row($handle, ['nombre', 'correo', 'facultad']);
            Csv::row($handle, ['Nombre Apellido', 'n00000000@upn.pe', $faculty?->name ?? 'Nombre de la facultad']);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Bulk-creates already-approved student accounts from a CSV roster
     * (columns: nombre, correo, and optionally facultad — see
     * importTemplate() above). Each row gets its own random password,
     * emailed individually — same mechanism as a single approval, just
     * looped. Invalid/duplicate rows are skipped and reported, never
     * silently dropped, and a student already in the system (matched by
     * their @upn.pe code, the part before the @) is never updated or
     * overwritten by a later row, even a repeat within the same file.
     */
    public function import(Request $request)
    {
        $actor = $request->user();

        $data = $request->validate([
            // max is in kilobytes — 2MB comfortably covers even a roster of
            // several thousand students as plain text, while still capping
            // how much a single upload can make this request process.
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'faculty_id' => [$actor->isAdmin() ? 'required' : 'nullable', 'exists:faculties,id'],
        ]);

        $defaultFacultyId = $actor->isAdmin() ? (int) $data['faculty_id'] : $actor->faculty_id;
        $facultiesByName = Faculty::all()->keyBy(fn (Faculty $f) => Str::lower(trim($f->name)));

        $handle = fopen($data['file']->getRealPath(), 'r');
        $firstLine = fgets($handle) ?: '';
        rewind($handle);
        $delimiter = Csv::detectDelimiter($firstLine);

        $header = array_map(
            fn ($value) => Str::lower(Csv::normalizeCell($value)),
            fgetcsv($handle, 0, $delimiter) ?: []
        );
        $nameIndex = array_search('nombre', $header, true);
        $emailIndex = array_search('correo', $header, true);
        $facultyIndex = array_search('facultad', $header, true);

        if ($nameIndex === false || $emailIndex === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'El CSV debe tener las columnas "nombre" y "correo".']);
        }

        $created = 0;
        $skipped = [];
        $passwords = [];
        $seenCodes = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $name = Csv::normalizeCell($row[$nameIndex] ?? '');
            $email = Str::lower(Csv::normalizeCell($row[$emailIndex] ?? ''));

            if ($name === '' || $email === '') {
                continue;
            }

            if (! str_ends_with($email, '@upn.pe')) {
                $skipped[] = "{$email} (no es @upn.pe)";

                continue;
            }

            // The student's code (e.g. n00321428) is the email's local part
            // — comparing by it instead of the raw string catches a repeat
            // row within this same file too, not only one already in the DB.
            $code = Str::before($email, '@');

            if (isset($seenCodes[$code]) || User::where('email', $email)->exists()) {
                $skipped[] = "{$email} (ya existe, se mantiene el registro anterior)";

                continue;
            }

            $seenCodes[$code] = true;

            $facultyId = $defaultFacultyId;

            if ($facultyIndex !== false) {
                $facultyCell = Str::lower(Csv::normalizeCell($row[$facultyIndex] ?? ''));
                $facultyId = $facultiesByName->get($facultyCell)?->id ?? $defaultFacultyId;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'faculty_id' => $facultyId,
            ]);

            $passwords[] = "{$email}: ".$this->temporaryPasswords->grant($user);
            $created++;
        }

        fclose($handle);

        $status = "{$created} cuenta(s) creada(s). Se intentó notificar por correo a cada una — por si no llega, ".
            "estas son sus contraseñas temporales (no vencen, solo sirven para el primer ingreso): ".
            implode(' · ', $passwords);

        if ($skipped) {
            $status .= ' | Omitidas: '.implode(', ', $skipped);
        }

        return back()->with('status', $status);
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
