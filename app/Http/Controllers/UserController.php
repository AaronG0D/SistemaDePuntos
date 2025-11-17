<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Obtener parámetros de filtro
        $search = $request->get('search');
        $role = $request->get('role', 'all');
        $tab = $request->get('tab', 'activos');
        $page = $request->get('page', 1);

        // Usuarios activos
        $queryActivos = User::query()
            ->whereNull('deleted_at')
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                      ->orWhere('primerApellido', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role && $role !== 'all', function($query) use ($role) {
                $query->where('rol', $role);
            })
            ->latest();

        // Usuarios inactivos
        $queryInactivos = User::onlyTrashed()
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                      ->orWhere('primerApellido', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role && $role !== 'all', function($query) use ($role) {
                $query->where('rol', $role);
            })
            ->latest();

        // Paginar ambas queries
        $usuariosActivos = $queryActivos->paginate(10, ['*'], 'page', $tab === 'activos' ? $page : 1);
        $usuariosInactivos = $queryInactivos->paginate(10, ['*'], 'page', $tab === 'inactivos' ? $page : 1);
        
        // Restaurar query strings
        $usuariosActivos->appends($request->query());
        $usuariosInactivos->appends($request->query());

        return Inertia::render('admin/Users/Index', [
            'users' => $usuariosActivos,
            'usuariosInactivos' => $usuariosInactivos,
            'filters' => [
                'search' => $search ?? '',
                'role' => $role,
                'tab' => $tab
            ]
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'primerApellido' => ['required', 'string', 'max:100'],
            'segundoApellido' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:usuario,email'],
            'rol' => ['required', 'in:estudiante,docente,administrador'],
            'password' => ['required', 'string', 'min:6'],
        ],[
            'nombres.required' => 'El campo nombres es obligatorio.',
            'nombres.string' => 'El nombre debe ser una cadena de texto.',
            'nombres.max' => 'El nombre debe tener como máximo 100 caracteres.',
            'primerApellido.required' => 'El campo primer apellido es obligatorio.',
            'primerApellido.string' => 'El primer apellido debe ser una cadena de texto.',
            'primerApellido.max' => 'El primer apellido debe tener como máximo 100 caracteres.',
            'email.required' => 'El campo email es obligatorio.',
            'email.email' => 'El email ingresado no es válido.',
            'email.max' => 'El email debe tener como máximo 100 caracteres.',
            'email.unique' => 'El email ya está registrado.',
            'rol.required' => 'El campo rol es obligatorio.',
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
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
            'nombres' => ['required', 'string', 'max:100'],
            'primerApellido' => ['required', 'string', 'max:100'],
            'segundoApellido' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:usuario,email,' . $user->id . ',id'],
            'rol' => ['required', 'in:estudiante,docente,administrador'],
            'password' => ['nullable', 'string', 'min:6'],
        ],[
            'nombres.required' => 'El campo nombres es obligatorio.',
            'nombres.string' => 'El nombre debe ser una cadena de texto.',
            'nombres.max' => 'El nombre debe tener como máximo 100 caracteres.',
            'primerApellido.required' => 'El campo primer apellido es obligatorio.',
            'primerApellido.string' => 'El primer apellido debe ser una cadena de texto.',
            'primerApellido.max' => 'El primer apellido debe tener como máximo 100 caracteres.',
            'email.required' => 'El campo email es obligatorio.',
            'email.email' => 'El email ingresado no es válido.',
            'email.max' => 'El email debe tener como máximo 100 caracteres.',
            'email.unique' => 'El email ya está registrado.',
            'rol.required' => 'El campo rol es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
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
        try {
            // Desactivar al usuario
            $user->delete();

            // Si es estudiante, desactivar también el registro de estudiante
            if ($user->estudiante) {
                $user->estudiante->delete();
            }

            // Si es docente, desactivar también el registro de docente
            if ($user->docente) {
                $user->docente->delete();
            }
            
            return redirect()->route('users.index')
                ->with('success', 'Usuario desactivado correctamente');
                
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->with('error', 'Error al desactivar el usuario: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);

            // Reactivar al usuario
            $user->restore();

            // Si tiene un estudiante desactivado, reactivarlo
            if ($user->estudiante && $user->estudiante->trashed()) {
                $user->estudiante->restore();
            }

            // Si tiene un docente desactivado, reactivarlo
            if ($user->docente && $user->docente->trashed()) {
                $user->docente->restore();
            }
            
            return redirect()->route('users.index')
                ->with('success', 'Usuario reactivado correctamente');
                
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->with('error', 'Error al reactivar el usuario: ' . $e->getMessage());
        }
    }
}
