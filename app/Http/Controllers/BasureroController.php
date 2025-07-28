<?php

namespace App\Http\Controllers;

use App\Models\Basurero;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BasureroController extends Controller
{
    public function index(): Response
    {
        $basureros = Basurero::withCount('depositos')
            ->orderBy('ubicacion')
            ->paginate(10);

        return Inertia::render('admin/residuos/BasurerosList', [
            'basureros' => $basureros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/residuos/BasureroCreate');
    }

    public function store(Request $request)
    {
        $request->validate([
            'ubicacion' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|boolean',
        ]);

        Basurero::create([
            'ubicacion' => $request->ubicacion,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado ? 1 : 0,
        ]);

        return redirect()->route('admin.basureros.index')
            ->with('success', 'Basurero creado exitosamente');
    }

    public function show(Basurero $basurero): Response
    {
        $basurero->load(['depositos' => function ($query) {
            $query->with(['user', 'tipoBasura'])
                ->orderBy('fechaHora', 'desc')
                ->limit(20);
        }]);

        return Inertia::render('admin/residuos/BasureroView', [
            'basurero' => $basurero,
        ]);
    }

    public function edit(Basurero $basurero): Response
    {
        return Inertia::render('admin/residuos/BasureroEdit', [
            'basurero' => $basurero,
        ]);
    }

    public function update(Request $request, Basurero $basurero)
    {
        $request->validate([
            'ubicacion' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|boolean',
        ]);

        $basurero->update([
            'ubicacion' => $request->ubicacion,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado ? 1 : 0,
        ]);

        return redirect()->route('admin.basureros.index')
            ->with('success', 'Basurero actualizado exitosamente');
    }

    public function destroy(Basurero $basurero)
    {
        // Verificar si tiene depósitos asociados
        if ($basurero->depositos()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un basurero que tiene depósitos asociados');
        }

        $basurero->delete();

        return redirect()->route('admin.basureros.index')
            ->with('success', 'Basurero eliminado exitosamente');
    }

    public function toggleEstado(Basurero $basurero)
    {
        if ($basurero->estado) {
            $basurero->desactivar();
            $mensaje = 'Basurero desactivado';
        } else {
            $basurero->activar();
            $mensaje = 'Basurero activado';
        }

        return back()->with('success', $mensaje);
    }
} 