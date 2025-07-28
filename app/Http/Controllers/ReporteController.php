<?php

namespace App\Http\Controllers;

use App\Models\Basurero;
use App\Models\Deposito;
use App\Models\TipoBasura;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    public function index()
    {
        $estadisticas = [
            'total_depositos' => Deposito::count(),
            'total_puntos' => Deposito::join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
                ->sum('tipoBasura.puntos'),
            'total_tipos_residuos' => TipoBasura::count(),
            'total_basureros' => Basurero::count(),
        ];

        $tiposResiduos = TipoBasura::select('idTipoBasura as id', 'nombre')->get();
        $basureros = Basurero::select('idBasurero as id', 'ubicacion')->get();

        // Datos para gráficos
        $depositosPorTipo = Deposito::join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->select('tipoBasura.nombre', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(tipoBasura.puntos) as puntos_totales'))
            ->groupBy('tipoBasura.idTipoBasura', 'tipoBasura.nombre')
            ->orderByDesc('cantidad')
            ->get();

        $depositosPorMes = Deposito::select(
            DB::raw('DATE_FORMAT(fechaHora, "%Y-%m") as mes'),
            DB::raw('COUNT(*) as cantidad')
        )
        ->where('fechaHora', '>=', now()->subMonths(6))
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();

        $topUsuarios = DB::table('usuario')
            ->select('usuario.nombres', 'usuario.primerApellido', DB::raw('SUM(tb.puntos) as total_puntos'))
            ->join('deposito', 'usuario.id', '=', 'deposito.idUser')
            ->join('tipoBasura as tb', 'deposito.idTipoBasura', '=', 'tb.idTipoBasura')
            ->groupBy('usuario.id', 'usuario.nombres', 'usuario.primerApellido')
            ->orderByDesc('total_puntos')
            ->limit(10)
            ->get();

        $datosGraficos = [
            'porTipo' => [
                'labels' => $depositosPorTipo->pluck('nombre')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Cantidad de Depósitos',
                        'data' => $depositosPorTipo->pluck('cantidad')->toArray(),
                        'backgroundColor' => [
                            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
                            '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
                        ],
                        'borderColor' => '#1F2937',
                        'borderWidth' => 1
                    ]
                ]
            ],
            'porMes' => [
                'labels' => $depositosPorMes->pluck('mes')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Depósitos por Mes',
                        'data' => $depositosPorMes->pluck('cantidad')->toArray(),
                        'borderColor' => '#3B82F6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderWidth' => 2,
                        'fill' => true
                    ]
                ]
            ],
            'topUsuarios' => [
                'labels' => $topUsuarios->map(fn($u) => $u->nombres . ' ' . $u->primerApellido)->toArray(),
                'datasets' => [
                    [
                        'label' => 'Puntos Totales',
                        'data' => $topUsuarios->pluck('total_puntos')->toArray(),
                        'backgroundColor' => [
                            '#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6',
                            '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
                        ],
                        'borderColor' => '#1F2937',
                        'borderWidth' => 1
                    ]
                ]
            ]
        ];

        return Inertia::render('admin/reportes/Index', [
            'estadisticas' => $estadisticas,
            'tiposResiduos' => $tiposResiduos,
            'basureros' => $basureros,
            'datosGraficos' => $datosGraficos
        ]);
    }

    public function depositos(Request $request)
    {
        $request->validate([
            'tipo_residuo_id' => 'nullable|exists:tipoBasura,idTipoBasura',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        $query = Deposito::with(['user', 'tipoBasura', 'basurero'])
            ->when($request->filled('tipo_residuo_id') && $request->tipo_residuo_id !== '', function($q) use ($request) {
                $q->where('idTipoBasura', $request->tipo_residuo_id);
            })
            ->when($request->filled('fecha_inicio'), function($q) use ($request) {
                $q->whereDate('fechaHora', '>=', $request->fecha_inicio);
            })
            ->when($request->filled('fecha_fin'), function($q) use ($request) {
                $q->whereDate('fechaHora', '<=', $request->fecha_fin);
            });

        return response()->json([
            'depositos' => $query->get(),
            'total' => $query->count()
        ]);
    }

    public function ranking(Request $request)
    {
        $request->validate([
            'periodo' => 'required|in:semana,mes,anio,todo'
        ]);

        $query = DB::table('usuario')
            ->select('usuario.*')
            ->selectRaw('(
                SELECT SUM(tb.puntos)
                FROM deposito d
                JOIN tipoBasura tb ON d.idTipoBasura = tb.idTipoBasura
                WHERE d.idUser = usuario.id
                AND ' . $this->getSqlPeriodo($request->periodo, 'd.fechaHora') . '
            ) as total_puntos')
            ->orderByDesc('total_puntos')
            ->limit(10);

        $usuarios = $query->get();

        return response()->json([
            'usuarios' => $usuarios,
            'total' => $usuarios->count()
        ]);
    }

    public function basureros(Request $request)
    {
        $request->validate([
            'basurero_id' => 'required|exists:basurero,idBasurero',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        $query = Deposito::with(['tipoBasura'])
            ->where('idBasurero', $request->basurero_id)
            ->when($request->filled('fecha_inicio'), function($q) use ($request) {
                $q->whereDate('fechaHora', '>=', $request->fecha_inicio);
            })
            ->when($request->filled('fecha_fin'), function($q) use ($request) {
                $q->whereDate('fechaHora', '<=', $request->fecha_fin);
            });

        return response()->json([
            'depositos' => $query->get(),
            'total' => $query->count(),
            'puntos_totales' => $query->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
                ->sum('tipoBasura.puntos')
        ]);
    }

    public function tendencias(Request $request)
    {
        $request->validate([
            'agrupacion' => 'required|in:dia,semana,mes',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        $format = $request->agrupacion === 'dia' ? '%Y-%m-%d' : 
                 ($request->agrupacion === 'semana' ? '%Y-%u' : '%Y-%m');

        $tendencias = Deposito::selectRaw("
                DATE_FORMAT(fechaHora, '{$format}') as periodo,
                COUNT(*) as total_depositos,
                SUM(tipoBasura.puntos) as total_puntos
            ")
            ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->whereDate('fechaHora', '>=', $request->fecha_inicio)
            ->whereDate('fechaHora', '<=', $request->fecha_fin)
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        return response()->json([
            'tendencias' => $tendencias
        ]);
    }

    public function impacto(Request $request)
    {
        $request->validate([
            'metrica' => 'required|in:co2,agua,energia',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        // Factores de conversión (ejemplo)
        $factores = [
            'co2' => 2.5, // kg CO2 por kg de residuo
            'agua' => 1000, // litros de agua por kg de residuo
            'energia' => 5 // kWh por kg de residuo
        ];

        $impacto = Deposito::join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->whereDate('fechaHora', '>=', $request->fecha_inicio)
            ->whereDate('fechaHora', '<=', $request->fecha_fin)
            ->get()
            ->sum(function($deposito) use ($request, $factores) {
                return $deposito->tipoBasura->peso * $factores[$request->metrica];
            });

        return response()->json([
            'impacto' => $impacto,
            'unidad' => $request->metrica === 'co2' ? 'kg CO2' : 
                       ($request->metrica === 'agua' ? 'litros' : 'kWh')
        ]);
    }

    // --- MÉTODO PARA EXPORTAR PDF DE DEPÓSITOS ---
    public function exportarPDF(Request $request)
    {
        $request->validate([
            'tipo_residuo_id' => 'nullable|exists:tipoBasura,idTipoBasura',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        $query = Deposito::with(['user', 'tipoBasura', 'basurero'])
            ->when($request->filled('tipo_residuo_id') && $request->tipo_residuo_id !== '', function($q) use ($request) {
                $q->where('idTipoBasura', $request->tipo_residuo_id);
            })
            ->when($request->filled('fecha_inicio'), function($q) use ($request) {
                $q->whereDate('fechaHora', '>=', $request->fecha_inicio);
            })
            ->when($request->filled('fecha_fin'), function($q) use ($request) {
                $q->whereDate('fechaHora', '<=', $request->fecha_fin);
            });

        $depositos = $query->get();
        $total = $depositos->count();
        $total_puntos = $depositos->sum(function($d) {
            return $d->tipoBasura->puntos ?? 0;
        });
        $fecha_generacion = now()->format('d/m/Y H:i');

        // Calcular estadísticas generales para el PDF
        $usuarios_unicos = $depositos->pluck('user.id')->unique()->count();
        $estadisticas = [
            'total_depositos' => $total,
            'total_puntos' => $total_puntos,
            'usuarios_unicos' => $usuarios_unicos,
        ];

        // Calcular desglose por tipo de basura
        $porTipoBasura = $depositos->groupBy('tipoBasura.nombre')->map(function($items, $nombre) {
            return [
                'nombre' => $nombre,
                'cantidad' => $items->count(),
                'puntos_totales' => $items->sum(function($d) {
                    return $d->tipoBasura->puntos ?? 0;
                }),
            ];
        })->values()->all();

        $pdf = Pdf::loadView('reportes.depositos-pdf', [
            'depositos' => $depositos,
            'total' => $total,
            'total_puntos' => $total_puntos,
            'filtros' => [
                'tipo_residuo_id' => $request->tipo_residuo_id,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
            ],
            'fecha_generacion' => $fecha_generacion,
            'estadisticas' => $estadisticas,
            'porTipoBasura' => $porTipoBasura,
        ]);
        return $pdf->download('reporte_depositos.pdf');
    }

    // --- PDF: Ranking por periodo y tipo de basura ---
    public function exportarRankingPDF(Request $request)
    {
        $request->validate([
            'periodo' => 'required|in:semana,mes,anio,todo',
            'tipo_residuo_id' => 'nullable|exists:tipoBasura,idTipoBasura',
        ]);

        $periodo = $request->periodo;
        $tipoResiduoId = $request->tipo_residuo_id;

        $usuarios = \DB::table('usuario')
            ->select('usuario.*')
            ->selectRaw('(
                SELECT SUM(tb.puntos)
                FROM deposito d
                JOIN tipoBasura tb ON d.idTipoBasura = tb.idTipoBasura
                WHERE d.idUser = usuario.id
                ' . ($tipoResiduoId && $tipoResiduoId !== '' ? 'AND d.idTipoBasura = ' . intval($tipoResiduoId) : '') . '
                AND ' . $this->getSqlPeriodo($periodo, 'd.fechaHora') . '
            ) as total_puntos')
            ->orderByDesc('total_puntos')
            ->limit(10)
            ->get();

        $fecha_generacion = now()->format('d/m/Y H:i');
        $total_puntos = $usuarios->sum('total_puntos');
        $estadisticas = [
            'total_usuarios' => $usuarios->count(),
            'total_puntos' => $total_puntos,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.ranking-pdf', [
            'usuarios' => $usuarios,
            'periodo' => $periodo,
            'tipo_residuo_id' => $tipoResiduoId,
            'fecha_generacion' => $fecha_generacion,
            'estadisticas' => $estadisticas,
        ]);
        return $pdf->download('reporte_ranking.pdf');
    }

    // --- PDF: Depósitos por basurero ---
    public function exportarBasureroPDF(Request $request)
    {
        $request->validate([
            'basurero_id' => 'required|exists:basurero,idBasurero',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        $query = \App\Models\Deposito::with(['user', 'tipoBasura', 'basurero'])
            ->where('idBasurero', $request->basurero_id)
            ->when($request->filled('fecha_inicio'), function($q) use ($request) {
                $q->whereDate('fechaHora', '>=', $request->fecha_inicio);
            })
            ->when($request->filled('fecha_fin'), function($q) use ($request) {
                $q->whereDate('fechaHora', '<=', $request->fecha_fin);
            });

        $depositos = $query->get();
        $fecha_generacion = now()->format('d/m/Y H:i');
        $total = $depositos->count();
        $total_puntos = $depositos->sum(function($d) {
            return $d->tipoBasura->puntos ?? 0;
        });
        $usuarios_unicos = $depositos->pluck('user.id')->unique()->count();
        $estadisticas = [
            'total_depositos' => $total,
            'total_puntos' => $total_puntos,
            'usuarios_unicos' => $usuarios_unicos,
        ];
        $porTipoBasura = $depositos->groupBy('tipoBasura.nombre')->map(function($items, $nombre) {
            return [
                'nombre' => $nombre,
                'cantidad' => $items->count(),
                'puntos_totales' => $items->sum(function($d) {
                    return $d->tipoBasura->puntos ?? 0;
                }),
            ];
        })->values()->all();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.deposito-basurero-pdf', [
            'depositos' => $depositos,
            'basurero_id' => $request->basurero_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'fecha_generacion' => $fecha_generacion,
            'estadisticas' => $estadisticas,
            'porTipoBasura' => $porTipoBasura,
        ]);
        return $pdf->download('reporte_basurero.pdf');
    }

    // --- PDF: Depósitos por fecha ---
    public function exportarDepositosPorFechaPDF(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        $query = \App\Models\Deposito::with(['user', 'tipoBasura', 'basurero'])
            ->whereDate('fechaHora', '>=', $request->fecha_inicio)
            ->whereDate('fechaHora', '<=', $request->fecha_fin);

        $depositos = $query->get();
        $fecha_generacion = now()->format('d/m/Y H:i');
        $total = $depositos->count();
        $total_puntos = $depositos->sum(function($d) {
            return $d->tipoBasura->puntos ?? 0;
        });
        $usuarios_unicos = $depositos->pluck('user.id')->unique()->count();
        $estadisticas = [
            'total_depositos' => $total,
            'total_puntos' => $total_puntos,
            'usuarios_unicos' => $usuarios_unicos,
        ];
        $porTipoBasura = $depositos->groupBy('tipoBasura.nombre')->map(function($items, $nombre) {
            return [
                'nombre' => $nombre,
                'cantidad' => $items->count(),
                'puntos_totales' => $items->sum(function($d) {
                    return $d->tipoBasura->puntos ?? 0;
                }),
            ];
        })->values()->all();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.deposito-fecha-pdf', [
            'depositos' => $depositos,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'fecha_generacion' => $fecha_generacion,
            'estadisticas' => $estadisticas,
            'porTipoBasura' => $porTipoBasura,
        ]);
        return $pdf->download('reporte_depositos_fecha.pdf');
    }

    // --- EXCEL: Exportar depósitos a Excel ---
    public function exportarExcel(Request $request)
    {
        $request->validate([
            'tipo_residuo_id' => 'nullable|exists:tipoBasura,idTipoBasura',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        $query = Deposito::with(['user', 'tipoBasura', 'basurero'])
            ->when($request->filled('tipo_residuo_id') && $request->tipo_residuo_id !== '', function($q) use ($request) {
                $q->where('idTipoBasura', $request->tipo_residuo_id);
            })
            ->when($request->filled('fecha_inicio'), function($q) use ($request) {
                $q->whereDate('fechaHora', '>=', $request->fecha_inicio);
            })
            ->when($request->filled('fecha_fin'), function($q) use ($request) {
                $q->whereDate('fechaHora', '<=', $request->fecha_fin);
            });

        $depositos = $query->get();

        // Crear archivo CSV (equivalente a Excel para este caso)
        $filename = 'reporte_depositos_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($depositos) {
            $file = fopen('php://output', 'w');
            
            // Encabezados
            fputcsv($file, [
                'ID', 'Usuario', 'Tipo de Basura', 'Basurero', 'Fecha y Hora', 'Puntos'
            ]);

            // Datos
            foreach ($depositos as $deposito) {
                fputcsv($file, [
                    $deposito->idDeposito,
                    $deposito->user ? $deposito->user->nombres . ' ' . $deposito->user->primerApellido : 'N/A',
                    $deposito->tipoBasura ? $deposito->tipoBasura->nombre : 'N/A',
                    $deposito->basurero ? $deposito->basurero->ubicacion : 'N/A',
                    $deposito->fechaHora,
                    $deposito->tipoBasura ? $deposito->tipoBasura->puntos : 0
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function aplicarFiltroPeriodo($query, $periodo)
    {
        $ahora = Carbon::now();
        
        switch ($periodo) {
            case 'semana':
                $query->whereBetween('fechaHora', [
                    $ahora->copy()->startOfWeek(),
                    $ahora->copy()->endOfWeek()
                ]);
                break;
            case 'mes':
                $query->whereBetween('fechaHora', [
                    $ahora->copy()->startOfMonth(),
                    $ahora->copy()->endOfMonth()
                ]);
                break;
            case 'anio':
                $query->whereBetween('fechaHora', [
                    $ahora->copy()->startOfYear(),
                    $ahora->copy()->endOfYear()
                ]);
                break;
        }
    }

    protected function getSqlPeriodo($periodo, $campo)
    {
        $ahora = Carbon::now();
        
        switch ($periodo) {
            case 'semana':
                return $campo . " BETWEEN '" . $ahora->startOfWeek()->format('Y-m-d H:i:s') . 
                       "' AND '" . $ahora->endOfWeek()->format('Y-m-d H:i:s') . "'";
            case 'mes':
                return $campo . " BETWEEN '" . $ahora->startOfMonth()->format('Y-m-d H:i:s') . 
                       "' AND '" . $ahora->endOfMonth()->format('Y-m-d H:i:s') . "'";
            case 'anio':
                return $campo . " BETWEEN '" . $ahora->startOfYear()->format('Y-m-d H:i:s') . 
                       "' AND '" . $ahora->endOfYear()->format('Y-m-d H:i:s') . "'";
            default:
                return '1=1'; // todo el tiempo
        }
    }
} 