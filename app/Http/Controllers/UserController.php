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
         = User::query()->latest()->paginate(10)->withQueryString();
        return Inertia::render('admin/Users/Index', [
            'users' => ,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Users/Create');
    }

    public function store(Request )
    {
        // TODO: implementar validación y creación
        return redirect()->route('users.index');
    }

    public function show(User ): Response
    {
        return Inertia::render('admin/Users/Show', [
            'user' => ,
        ]);
     }

    public function edit(User ): Response
    {
        return Inertia::render('admin/Users/Edit', [
            'user' => ,
        ]);
    }

    public function update(Request , User )
    {
        // TODO: implementar actualización
        return redirect()->route('users.index');
    }

    public function destroy(User )
    {
        // TODO: implementar eliminación
        return redirect()->route('users.index');
    }
}
