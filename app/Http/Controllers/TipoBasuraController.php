<?php

namespace App\Http\Controllers;

use App\Models\TipoBasura;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TipoBasuraController extends Controller
{
    public function index(): Response
    {
        $tiposBasura = TipoBasura::withCount('depositos')
            ->ordenadosPorPuntos()
            ->paginate(10);

        return Inertia::render('admin/residuos/TiposBasuraList', [
            'tiposBasura' => $tiposBasura,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/residuos/TipoBasuraCreate');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:tipoBasura,nombre',
            'descripcion' => 'nullable|string',
            'puntos' => 'required|integer|min:1|max:1000',
        ]);

        TipoBasura::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'puntos' => $request->puntos,
        ]);

        return redirect()->route('admin.tipos-basura.index')
            ->with('success', 'Tipo de basura creado exitosamente');
    }

    public function show(TipoBasura $tipoBasura): Response
    {
        $tipoBasura->load(['depositos' => function ($query) {
            $query->with(['user', 'basurero'])
                ->orderBy('fechaHora', 'desc')
                ->limit(20);
        }]);

        return Inertia::render('admin/residuos/TipoBasuraView', [
            'tipoBasura' => $tipoBasura,
        ]);
    }

    public function edit(TipoBasura $tipoBasura): Response
    {
        return Inertia::render('admin/residuos/TipoBasuraEdit', [
            'tipoBasura' => $tipoBasura,
        ]);
    }

    public function update(Request $request, TipoBasura $tipoBasura)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:tipoBasura,nombre,' . $tipoBasura->idTipoBasura . ',idTipoBasura',
            'descripcion' => 'nullable|string',
            'puntos' => 'required|integer|min:1|max:1000',
        ]);

        $tipoBasura->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'puntos' => $request->puntos,
        ]);

        return redirect()->route('admin.tipos-basura.index')
            ->with('success', 'Tipo de basura actualizado exitosamente');
    }

    public function destroy(TipoBasura $tipoBasura)
    {
        // Verificar si tiene depósitos asociados
        if ($tipoBasura->depositos()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un tipo de basura que tiene depósitos asociados');
        }

        $tipoBasura->delete();

        return redirect()->route('admin.tipos-basura.index')
            ->with('success', 'Tipo de basura eliminado exitosamente');
    }

    public function toggleEstado(TipoBasura $tipoBasura)
    {
        $tipoBasura->estado = $tipoBasura->estado ? 0 : 1;
        $tipoBasura->save();

        return back()->with('success', $tipoBasura->estado ? 'Tipo de basura activado' : 'Tipo de basura desactivado');
    }
}