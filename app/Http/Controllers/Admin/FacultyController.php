<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

/**
 * Master-admin-only: faculties and their dean (decano) accounts. A dean is
 * a staff account scoped to a single faculty — they can only see/manage
 * students within it (see User::canManage()) and never reach this panel
 * themselves (it's outside the 'staff' middleware group, 'admin' only).
 */
class FacultyController extends Controller
{
    public function index()
    {
        $faculties = Faculty::query()
            ->withCount(['students'])
            ->with(['users' => fn ($query) => $query->where('role', 'decano')])
            ->orderBy('name')
            ->get();

        return view('admin.faculties.index', ['faculties' => $faculties]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:faculties,name'],
        ]);

        Faculty::create($data);

        return back()->with('status', 'Facultad creada correctamente.');
    }

    public function storeDean(Request $request, Faculty $faculty)
    {
        abort_if($faculty->dean(), 422, 'Esta facultad ya tiene un decano asignado.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'decano',
            'faculty_id' => $faculty->id,
            'email_verified_at' => now(),
        ]);

        return back()->with('status', 'Cuenta de decano creada correctamente.');
    }
}
