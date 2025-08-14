<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
        // TODO: implementar validación y creación
        return redirect()->route('users.index');
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
        // TODO: implementar actualización
        return redirect()->route('users.index');
    }

    public function destroy(User $user)
    {
        // TODO: implementar eliminación
        return redirect()->route('users.index');
    }
}
