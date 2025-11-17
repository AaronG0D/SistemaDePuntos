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
            'nombre' => 'required|string|max:50|unique:tipoBasura,nombre',
            'descripcion' => 'nullable|string',
            'puntos' => 'required|integer|min:1|max:10',
        ], [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.unique' => 'El nombre ya está en uso.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'puntos.required' => 'El campo puntos es obligatorio.',
            'puntos.integer' => 'Los puntos deben ser un número entero.',
            'puntos.min' => 'Los puntos deben ser al menos 1.',
            'puntos.max' => 'Los puntos no pueden superar los 10.',
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
        
        $totalDepositos = $tipoBasura->depositos()->count();
        $totalPuntos = $tipoBasura->puntos * $totalDepositos;
        $usuariosUnicos = $tipoBasura->depositos()->distinct('idUser')->count('idUser');
        $promedioPorUsuario = $totalPuntos / $usuariosUnicos;
        $BasureroUnicos = $tipoBasura->depositos()->distinct('idBasurero')->count('idBasurero');



        return Inertia::render('admin/residuos/TipoBasuraView', [
            'tipoBasura' => $tipoBasura,
            'totalDepositos' => $totalDepositos,
            'totalPuntos' => $totalPuntos,
            'usuariosUnicos' => $usuariosUnicos,
            'promedioPorUsuario' => $promedioPorUsuario,
            'basurerosUnicos' => $BasureroUnicos,    

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
            'nombre' => 'required|string|max:50|unique:tipoBasura,nombre,' . $tipoBasura->idTipoBasura . ',idTipoBasura',
            'descripcion' => 'nullable|string',
            'puntos' => 'required|integer|min:1|max:10',
        ], [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.unique' => 'El nombre ya está en uso.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'puntos.required' => 'El campo puntos es obligatorio.',
            'puntos.integer' => 'Los puntos deben ser un número entero.',
            'puntos.min' => 'Los puntos deben ser al menos 1.',
            'puntos.max' => 'Los puntos no pueden superar los 10.',
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

    public function restore($id)
    {
        $tipoBasura = TipoBasura::withTrashed()->findOrFail($id);
        $tipoBasura->restore();

        return redirect()->route('admin.tipos-basura.index')
            ->with('success', 'Tipo de basura restaurado exitosamente');
    }

    public function forceDelete($id)
    {
        $tipoBasura = TipoBasura::withTrashed()->findOrFail($id);
        
        // Verificar si tiene depósitos asociados
        if ($tipoBasura->depositos()->count() > 0) {
            return back()->with('error', 'No se puede eliminar permanentemente un tipo de basura que tiene depósitos asociados');
        }

        $tipoBasura->forceDelete();

        return redirect()->route('admin.tipos-basura.index')
            ->with('success', 'Tipo de basura eliminado permanentemente');
    }
}