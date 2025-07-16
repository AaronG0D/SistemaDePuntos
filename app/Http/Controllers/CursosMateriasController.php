<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Paralelo;
use App\Models\Materia;
use App\Models\CursoParalelo;
use App\Models\MateriaCursoParalelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CursosMateriasController extends Controller
{
    /**
     * Página principal de administración de cursos y materias
     */
    public function index()
    {
        $cursos = Curso::with(['cursoParalelos.paralelo', 'cursoParalelos.materias'])->get();
        $paralelos = Paralelo::orderBy('nombre')->get();
        $materias = Materia::orderBy('nombre')->get();



        return Inertia::render('admin/CursosMaterias', [
            'cursos' => $cursos,
            'paralelos' => $paralelos,
            'materias' => $materias,
        ]);
    }

    /**
     * Obtener materias de un curso-paralelo específico
     */
    public function getMateriasByCursoParalelo(Request $request)
    {
        $request->validate([
            'idCurso' => 'required|exists:curso,idCurso',
            'idParalelo' => 'required|exists:paralelo,idParalelo',
        ]);

        $cursoParalelo = CursoParalelo::where('idCurso', $request->idCurso)
            ->where('idParalelo', $request->idParalelo)
            ->first();

        if (!$cursoParalelo) {
            return response()->json(['error' => 'Curso-paralelo no encontrado'], 404);
        }

        $materiasAsignadas = $cursoParalelo->materias;
        $materiasDisponibles = Materia::whereNotIn('idMateria', $materiasAsignadas->pluck('idMateria'))->get();

        return response()->json([
            'cursoParalelo' => $cursoParalelo,
            'materiasAsignadas' => $materiasAsignadas,
            'materiasDisponibles' => $materiasDisponibles,
        ]);
    }

    /**
     * Asignar materia a un curso-paralelo
     */
    public function asignarMateria(Request $request)
    {
        $request->validate([
            'idCurso' => 'required|exists:curso,idCurso',
            'idParalelo' => 'required|exists:paralelo,idParalelo',
            'idMateria' => 'required|exists:materia,idMateria',
        ]);

        $cursoParalelo = CursoParalelo::where('idCurso', $request->idCurso)
            ->where('idParalelo', $request->idParalelo)
            ->first();

        if (!$cursoParalelo) {
            return redirect()->back()->with('error', 'Curso-paralelo no encontrado');
        }

        // Verificar si ya existe la relación
        $existe = MateriaCursoParalelo::where('idCursoParalelo', $cursoParalelo->idCursoParalelo)
            ->where('idMateria', $request->idMateria)
            ->exists();

        if ($existe) {
            return redirect()->back()->with('error', 'La materia ya está asignada a este curso-paralelo');
        }

        // Crear la relación
        MateriaCursoParalelo::create([
            'idCursoParalelo' => $cursoParalelo->idCursoParalelo,
            'idMateria' => $request->idMateria,
        ]);

        return redirect()->back()->with('success', 'Materia asignada correctamente');
    }

    /**
     * Quitar materia de un curso-paralelo
     */
    public function quitarMateria(Request $request)
    {
        $request->validate([
            'idCurso' => 'required|exists:curso,idCurso',
            'idParalelo' => 'required|exists:paralelo,idParalelo',
            'idMateria' => 'required|exists:materia,idMateria',
        ]);

        $cursoParalelo = CursoParalelo::where('idCurso', $request->idCurso)
            ->where('idParalelo', $request->idParalelo)
            ->first();

        if (!$cursoParalelo) {
            return redirect()->back()->with('error', 'Curso-paralelo no encontrado');
        }

        // Verificar si hay docentes asignados a esta materia en este curso-paralelo
        $docentesAsignados = DB::table('docente_materia_curso')
            ->where('idCursoParalelo', $cursoParalelo->idCursoParalelo)
            ->where('idMateria', $request->idMateria)
            ->count();

        if ($docentesAsignados > 0) {
            return redirect()->back()->with('error', 'No se puede quitar la materia porque hay docentes asignados a ella en este curso-paralelo');
        }

        // Eliminar la relación
        MateriaCursoParalelo::where('idCursoParalelo', $cursoParalelo->idCursoParalelo)
            ->where('idMateria', $request->idMateria)
            ->delete();

        return redirect()->back()->with('success', 'Materia quitada correctamente');
    }

    // ===== CRUD DE CURSOS =====

    public function storeCurso(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:curso,nombre',
        ]);

        $curso = Curso::create([
            'nombre' => $request->nombre,
        ]);

        // Crear curso-paralelos para todos los paralelos existentes
        $paralelos = Paralelo::all();
        foreach ($paralelos as $paralelo) {
            CursoParalelo::create([
                'idCurso' => $curso->idCurso,
                'idParalelo' => $paralelo->idParalelo,
            ]);
        }

        return redirect()->back()->with('success', 'Curso creado correctamente');
    }

    public function updateCurso(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:curso,nombre,' . $id . ',idCurso',
        ]);

        $curso = Curso::findOrFail($id);
        $curso->update(['nombre' => $request->nombre]);

        return redirect()->back()->with('success', 'Curso actualizado correctamente');
    }

    public function destroyCurso($id)
    {
        $curso = Curso::findOrFail($id);

        // Verificar si hay estudiantes en este curso
        $estudiantes = DB::table('estudiante')
            ->join('curso_paralelo', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
            ->where('curso_paralelo.idCurso', $id)
            ->count();

        if ($estudiantes > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar el curso porque tiene estudiantes asignados');
        }

        // Verificar si hay docentes asignados a este curso
        $docentes = DB::table('docente_materia_curso')
            ->join('curso_paralelo', 'docente_materia_curso.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
            ->where('curso_paralelo.idCurso', $id)
            ->count();

        if ($docentes > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar el curso porque tiene docentes asignados');
        }

        $curso->delete();

        return redirect()->back()->with('success', 'Curso eliminado correctamente');
    }

    // ===== CRUD DE PARALELOS =====

    public function storeParalelo(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:paralelo,nombre',
        ]);

        $paralelo = Paralelo::create([
            'nombre' => $request->nombre,
        ]);

        // Crear curso-paralelos para todos los cursos existentes
        $cursos = Curso::all();
        foreach ($cursos as $curso) {
            CursoParalelo::create([
                'idCurso' => $curso->idCurso,
                'idParalelo' => $paralelo->idParalelo,
            ]);
        }

        return redirect()->back()->with('success', 'Paralelo creado correctamente');
    }

    public function updateParalelo(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:paralelo,nombre,' . $id . ',idParalelo',
        ]);

        $paralelo = Paralelo::findOrFail($id);
        $paralelo->update(['nombre' => $request->nombre]);

        return redirect()->back()->with('success', 'Paralelo actualizado correctamente');
    }

    public function destroyParalelo($id)
    {
        $paralelo = Paralelo::findOrFail($id);

        // Verificar si hay estudiantes en este paralelo
        $estudiantes = DB::table('estudiante')
            ->join('curso_paralelo', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
            ->where('curso_paralelo.idParalelo', $id)
            ->count();

        if ($estudiantes > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar el paralelo porque tiene estudiantes asignados');
        }

        // Verificar si hay docentes asignados a este paralelo
        $docentes = DB::table('docente_materia_curso')
            ->join('curso_paralelo', 'docente_materia_curso.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
            ->where('curso_paralelo.idParalelo', $id)
            ->count();

        if ($docentes > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar el paralelo porque tiene docentes asignados');
        }

        $paralelo->delete();

        return redirect()->back()->with('success', 'Paralelo eliminado correctamente');
    }

    // ===== CRUD DE MATERIAS =====

    public function storeMateria(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:materia,nombre',
        ]);

        $materia = Materia::create([
            'nombre' => $request->nombre,
        ]);

        return redirect()->back()->with('success', 'Materia creada correctamente');
    }

    public function updateMateria(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:materia,nombre,' . $id . ',idMateria',
        ]);

        $materia = Materia::findOrFail($id);
        $materia->update(['nombre' => $request->nombre]);

        return redirect()->back()->with('success', 'Materia actualizada correctamente');
    }

    public function destroyMateria($id)
    {
        $materia = Materia::findOrFail($id);

        // Verificar si hay docentes asignados a esta materia
        $docentes = DB::table('docente_materia_curso')
            ->where('idMateria', $id)
            ->count();

        if ($docentes > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar la materia porque tiene docentes asignados');
        }

        $materia->delete();

        return redirect()->back()->with('success', 'Materia eliminada correctamente');
    }
} 