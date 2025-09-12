<?php

namespace App\Http\Controllers;

use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeriodoAcademicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $periodos = PeriodoAcademico::query()
            ->when($request->search, function($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            })
            ->when($request->year, function($query, $year) {
                $query->whereYear('fecha_inicio', $year);
            })
            ->when($request->estado, function($query, $estado) {
                $query->where('activo', $estado === 'activo');
            })
            ->latest()
            ->get();

        $years = PeriodoAcademico::selectRaw('YEAR(fecha_inicio) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return Inertia::render('admin/PeriodosAcademicos/Index', [
            'periodos' => $periodos,
            'filters' => $request->only(['search', 'year', 'estado']),
            'years' => $years
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|max:50|unique:periodos_academicos',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'boolean'
        ]);

        PeriodoAcademico::create($validated);

        return redirect()->back()->with('success', 'Período académico creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(PeriodoAcademico $periodoAcademico)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PeriodoAcademico $periodo)
    {
        try {
            
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'codigo' => [
                    'required',
                    'string',
                    'max:50',
                    \Illuminate\Validation\Rule::unique('periodos_academicos', 'codigo')
                        ->ignore($periodo->idPeriodo, 'idPeriodo')
                ],
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'activo' => 'boolean'
            ], [
                'codigo.unique' => 'El código ya está en uso por otro período académico.',
            ]);

            $periodo->fill($validated);
            $periodo->save();

            return redirect()->back()->with('success', 'Período académico actualizado exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            
            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar el período académico'])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PeriodoAcademico $periodo)
    {
        try {
            // Verificar si hay puntajes asociados
            if ($periodo->puntajes()->exists()) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar el período académico porque tiene puntajes asociados.');
            }

            $periodo->delete();
            return redirect()->back()
                ->with('success', 'Período académico eliminado exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error al eliminar período:', [
                'id' => $periodo->idPeriodo,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'No se puede eliminar el período académico. Verifica que no tenga registros asociados.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
}
