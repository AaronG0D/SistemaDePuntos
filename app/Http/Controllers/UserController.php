<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()->latest()->paginate(10)->withQueryString();
        return Inertia::render('admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:255'],
            'primerApellido' => ['required', 'string', 'max:255'],
            'segundoApellido' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuario,email'],
            'rol' => ['required', 'in:estudiante,docente,administrador'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        // Generar qr_codigo basado en nombre y apellidos
        $fullName = trim(($validated['nombres'] ?? '') . ' ' . ($validated['primerApellido'] ?? '') . ' ' . ($validated['segundoApellido'] ?? ''));
        $baseCode = Str::slug(preg_replace('/\s+/', ' ', $fullName));
        // Sufijo corto para evitar colisiones
        $validated['qr_codigo'] = $baseCode ? ($baseCode . '-' . Str::lower(Str::random(6))) : Str::lower(Str::random(8));

        // La contraseña se hash-ea automáticamente por el cast en el modelo
        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente');
    }

    public function show(User $user): Response
    {
        return Inertia::render('admin/Users/Show', [
            'user' => $user,
        ]);
     }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:255'],
            'primerApellido' => ['required', 'string', 'max:255'],
            'segundoApellido' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuario,email,' . $user->id . ',id'],
            'rol' => ['required', 'in:estudiante,docente,administrador'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        // Si password viene vacío, no actualizar el campo
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Regenerar qr_codigo si cambian nombre o apellidos
        $fullName = trim(($validated['nombres'] ?? $user->nombres) . ' ' . ($validated['primerApellido'] ?? $user->primerApellido) . ' ' . ($validated['segundoApellido'] ?? $user->segundoApellido));
        $baseCode = Str::slug(preg_replace('/\s+/', ' ', $fullName));
        $validated['qr_codigo'] = $baseCode ? ($baseCode . '-' . Str::lower(Str::random(6))) : Str::lower(Str::random(8));

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente');
    }
}
