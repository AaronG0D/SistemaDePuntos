<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'primerApellido' => 'required|string|max:100',
            'segundoApellido' => 'nullable|string|max:100',
            'rol' => 'required|in:estudiante,docente,administrador',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'El nombre es obligatorio y debe tener un máximo de 255 caracteres.',
            'primerApellido.required' => 'El primer apellido es obligatorio y debe tener un máximo de 100 caracteres.',
            'segundoApellido.max' => 'El segundo apellido debe tener un máximo de 100 caracteres.',
            'rol.required' => 'El rol es obligatorio y debe ser estudiante, docente o administrador.',
            'email.required' => 'El correo electrónico es obligatorio, debe ser válido y único.',
            'password.required' => 'La contraseña es obligatoria y debe confirmarse.',
        ]);

        $user = User::create([
            'nombres' => $request->name,
            'primerApellido' => $request->primerApellido,
            'segundoApellido' => $request->segundoApellido,
            'rol' => $request->rol,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
