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
        // Si no se especifica un año o es "all", usar el año actual por defecto
        $year = ($request->year && $request->year !== 'all') ? $request->year : now()->year;
        
        $query = PeriodoAcademico::query();
        
        // Filtrar por estado de eliminación
        if ($request->trashed === 'only') {
            $query->onlyTrashed();
        } elseif ($request->trashed === 'with') {
            $query->withTrashed();
        }
        // Por defecto: solo activos (sin trashed)
        
        $periodos = $query
            ->when($request->search, function($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            })
            ->when($year && $request->year !== 'all', function($query) use ($year) {
                $query->whereYear('fecha_inicio', $year);
            })
            ->latest()
            ->get();

        $years = PeriodoAcademico::withTrashed()
            ->selectRaw('YEAR(fecha_inicio) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return Inertia::render('admin/PeriodosAcademicos/Index', [
            'periodos' => $periodos,
            'filters' => array_merge(
                $request->only(['search', 'year', 'trashed']),
                [
                    'year' => $request->year ?? 'all',
                    'trashed' => $request->trashed ?? 'active',
                ]
            ),
            'years' => $years
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'codigo' => 'required|string|max:10|unique:periodos_academicos',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'boolean'
        ],[
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'codigo.required' => 'El campo código es obligatorio.',
            'codigo.max' => 'El código no puede superar los 10 caracteres.',
            'codigo.string' => 'El código debe ser una cadena de texto.',
            'fecha_inicio.required' => 'El campo fecha de inicio es obligatorio.',
            'fecha_fin.required' => 'El campo fecha de fin es obligatorio.',
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
                    'max:10',
                    \Illuminate\Validation\Rule::unique('periodos_academicos', 'codigo')
                        ->ignore($periodo->idPeriodo, 'idPeriodo')
                ],
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'activo' => 'boolean'
            ], [
                'codigo.unique' => 'El código ya está en uso por otro período académico.',
                'codigo.required' => 'El campo código es obligatorio.',
                'codigo.max' => 'El código no puede superar los 10 caracteres.',
                'nombre.required' => 'El campo nombre es obligatorio.',
                'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
                'nombre.string' => 'El nombre debe ser una cadena de texto.',
            ]);

            // Si se está activando este período, desactivar todos los demás
            if ($validated['activo'] && !$periodo->activo) {
                PeriodoAcademico::where('idPeriodo', '!=', $periodo->idPeriodo)->update(['activo' => false]);
            }

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

    /**
     * Restore a soft-deleted academic period.
     */
    public function restore($id)
    {
        try {
            $periodo = PeriodoAcademico::withTrashed()->findOrFail($id);
            
            if (!$periodo->trashed()) {
                return redirect()->back()
                    ->with('error', 'El período académico no está eliminado.');
            }
            
            $periodo->restore();
            
            return redirect()->back()
                ->with('success', 'Período académico restaurado exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error al restaurar período:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'No se pudo restaurar el período académico.');
        }
    }
}
