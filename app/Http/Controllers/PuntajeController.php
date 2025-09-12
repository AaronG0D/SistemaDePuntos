<?php

namespace App\Http\Controllers;

use App\Models\Puntaje;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use App\Http\Requests\StorePuntajeRequest as StorePuntajeRequest;
use Illuminate\Support\Facades\Validator;

class PuntajeController extends Controller
{
    public function index(Request $request)
    {
        $query = Puntaje::with(['user', 'periodoAcademico']);

        if ($request->has('periodo_activo')) {
            $query->whereHas('periodoAcademico', function ($q) {
                $q->where('activo', true);
            });
        }

        $puntajes = $query->latest()->paginate(15);

        return response()->json($puntajes);
    }

    public function store(HttpRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'idUser' => 'required|exists:users,id',
            'puntos' => 'required|integer|min:1',
            'idPeriodo' => 'sometimes|exists:periodos_academicos,idPeriodo',
            'comentario' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        if (!isset($data['idPeriodo'])) {
            $periodoActivo = PeriodoAcademico::where('activo', true)->first();
            if (!$periodoActivo) {
                return response()->json(['message' => 'No hay un período académico activo.'], 404);
            }
            $data['idPeriodo'] = $periodoActivo->idPeriodo;
        }

        $puntaje = Puntaje::create($data);

        return response()->json($puntaje->load(['user', 'periodoAcademico']), 201);
    }

    public function show(Puntaje $puntaje)
    {
        return response()->json($puntaje->load(['user', 'periodoAcademico']));
    }

    public function update(HttpRequest $request, Puntaje $puntaje)
    {
        $validator = Validator::make($request->all(), [
            'puntos' => 'sometimes|required|integer|min:1',
            'comentario' => 'nullable|string|max:500',
            'estado' => 'sometimes|required|in:activo,asignado,acumulado',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $puntaje->update($validator->validated());

        return response()->json($puntaje->load(['user', 'periodoAcademico']));
    }

    public function destroy(Puntaje $puntaje)
    {
        $puntaje->delete();

        return response()->json(null, 204);
    }
}
