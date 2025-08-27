<?php

namespace App\Http\Controllers;

use App\Models\Basurero;
use App\Models\Deposito;
use App\Models\TipoBasura;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepositoController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Deposito::with(['user', 'basurero', 'tipoBasura'])
            ->orderBy('fechaHora', 'desc');

        // Filtros
        if ($request->filled('usuario')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('nombres', 'like', '%' . $request->usuario . '%')
                  ->orWhere('primerApellido', 'like', '%' . $request->usuario . '%');
            });
        }

        if ($request->filled('basurero')) {
            $query->where('idBasurero', $request->basurero);
        }

        if ($request->filled('tipo_basura')) {
            $query->where('idTipoBasura', $request->tipo_basura);
        }

        if ($request->filled('fecha')) {
            $query->porFecha($request->fecha);
        }

        $depositos = $query->paginate(15);
        

        // Datos para filtros
        $basureros = Basurero::activos()->get();
        $tiposBasura = TipoBasura::all();

        return Inertia::render('admin/residuos/DepositosList', [
            'depositos' => $depositos,
            'basureros' => $basureros,
            'tiposBasura' => $tiposBasura,
            'filters' => $request->only(['usuario', 'basurero', 'tipo_basura', 'fecha']),
        ]);
    }

    public function create(): Response
    {
        $basureros = Basurero::activos()->get();
        $tiposBasura = TipoBasura::all();

        return Inertia::render('admin/residuos/DepositoCreate', [
            'basureros' => $basureros,
            'tiposBasura' => $tiposBasura,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'idUser' => 'required|exists:usuario,id',
            'idBasurero' => 'required|exists:basurero,idBasurero',
            'idTipoBasura' => 'required|exists:tipoBasura,idTipoBasura',
            'fechaHora' => 'nullable|date',
        ]);

        Deposito::create([
            'idUser' => $request->idUser,
            'idBasurero' => $request->idBasurero,
            'idTipoBasura' => $request->idTipoBasura,
            'fechaHora' => $request->fechaHora ?? now(),
        ]);

        return redirect()->route('admin.depositos.index')
            ->with('success', 'Depósito registrado exitosamente');
    }

    public function show(Deposito $deposito): Response
    {
        $deposito->load(['user', 'basurero', 'tipoBasura']);

        return Inertia::render('admin/residuos/DepositoView', [
            'deposito' => $deposito,
        ]);
    }

    public function edit(Deposito $deposito): Response
    {
        $deposito->load(['user', 'basurero', 'tipoBasura']);
        $basureros = Basurero::activos()->get();
        $tiposBasura = TipoBasura::all();

        return Inertia::render('admin/residuos/DepositoEdit', [
            'deposito' => $deposito,
            'basureros' => $basureros,
            'tiposBasura' => $tiposBasura,
        ]);
    }

    public function update(Request $request, Deposito $deposito)
    {
        $request->validate([
            'idUser' => 'required|exists:usuario,id',
            'idBasurero' => 'required|exists:basurero,idBasurero',
            'idTipoBasura' => 'required|exists:tipoBasura,idTipoBasura',
            'fechaHora' => 'required|date',
        ]);

        $deposito->update([
            'idUser' => $request->idUser,
            'idBasurero' => $request->idBasurero,
            'idTipoBasura' => $request->idTipoBasura,
            'fechaHora' => $request->fechaHora,
        ]);

        return redirect()->route('admin.depositos.index')
            ->with('success', 'Depósito actualizado exitosamente');
    }

    public function destroy(Deposito $deposito)
    {
        $deposito->delete();

        return redirect()->route('admin.depositos.index')
            ->with('success', 'Depósito eliminado exitosamente');
    }

    public function estadisticas(): Response
    {
        $estadisticas = [
            'total_depositos' => Deposito::count(),
            'depositos_hoy' => Deposito::porFecha(now()->toDateString())->count(),
            'depositos_semana' => Deposito::recientes(7)->count(),
            'depositos_mes' => Deposito::recientes(30)->count(),
            'total_puntos' => Deposito::with('tipoBasura')->get()->sum('puntos_generados'),
            'usuarios_activos' => Deposito::select('idUser')->distinct()->count(),
        ];

        // Top 5 usuarios con más puntos
        $topUsuarios = Deposito::with('user')
            ->with('tipoBasura')
            ->get()
            ->groupBy('idUser')
            ->map(function($depositos) {
                return [
                    'idUser' => $depositos->first()->idUser,
                    'total_puntos' => $depositos->sum('puntos_generados'),
                    'user' => $depositos->first()->user
                ];
            })
            ->sortByDesc('total_puntos')
            ->take(5)
            ->values();

        // Top 5 tipos de basura más depositados
        $topTiposBasura = Deposito::with('tipoBasura')
            ->get()
            ->groupBy('idTipoBasura')
            ->map(function($depositos) {
                return [
                    'idTipoBasura' => $depositos->first()->idTipoBasura,
                    'total_depositos' => $depositos->count(),
                    'tipoBasura' => $depositos->first()->tipoBasura
                ];
            })
            ->sortByDesc('total_depositos')
            ->take(5)
            ->values();

        return Inertia::render('admin/residuos/Estadisticas', [
            'estadisticas' => $estadisticas,
            'topUsuarios' => $topUsuarios,
            'topTiposBasura' => $topTiposBasura,
        ]);
    }
} 