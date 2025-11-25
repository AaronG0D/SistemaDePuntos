<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RaspberryEvent;
use App\Models\User;
use App\Models\TipoBasura;
use App\Models\Deposito;
use App\Models\Basurero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RaspberryController extends Controller
{
    /**
     * Método de prueba simple
     */
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'RaspberryController funcionando',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Procesa un depósito enviado desde la Raspberry Pi
     */
    public function deposito(Request $request)
    {
        // Crear evento inicial
        $event = RaspberryEvent::create([
            'qr_codigo' => $request->input('qr_codigo'),
            'tipo_basura_nombre' => $request->input('tipo_basura'),
            'status' => 'pending',
            'message' => 'Procesando depósito',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [
                'action' => 'deposito',
                'peso' => $request->input('peso'),
                'timestamp_inicio' => now()->toISOString(),
                'request_data' => $request->all(),
            ],
        ]);

        try {
            $data = $request->validate([
                'qr_codigo' => 'required|string',
                'tipo_basura' => 'required|string',
               
            ], [
                'qr_codigo.required' => 'El campo código QR es obligatorio.',
                'qr_codigo.string' => 'El código QR debe ser una cadena de texto.',
                'tipo_basura.required' => 'El campo tipo de basura es obligatorio.',
                'tipo_basura.string' => 'El tipo de basura debe ser una cadena de texto.',
            ]);

            // 1) Buscar usuario por código QR
            $user = User::where('qr_codigo', $data['qr_codigo'])->first();

            if (!$user) {
                $event->update([
                    'status' => 'failed',
                    'message' => 'Usuario no encontrado',
                    'processed_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // 2) Verificar que sea un estudiante
            if ($user->rol !== 'estudiante') {
                $event->update([
                    'idUser' => $user->id,
                    'status' => 'failed',
                    'message' => 'El usuario no es un estudiante',
                    'processed_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no es un estudiante'
                ], 422);
            }
            $basurero=Basurero::where('estado',true)->first();
            if(!$basurero){
                $event->update([
                    'idUser' => $user->id,
                    'status' => 'failed',
                    'message' => 'Basurero no encontrado',
                    'processed_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Basurero Inactivo'
                ], 404);
            }

            // 3) Buscar tipo de basura
            $tipoBasura = TipoBasura::where('nombre', 'LIKE', '%' . $data['tipo_basura'] . '%')
                ->where('estado', true)
                ->first();

            if (!$tipoBasura) {
                $event->update([
                    'idUser' => $user->id,
                    'status' => 'failed',
                    'message' => $data['tipo_basura'] . ' no Activo',
                    'processed_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $data['tipo_basura'] . ' no Activo'
                ], 422);
            }

            // 4) Determinar período académico para la fecha del depósito (ahora)
            $fechaDeposito = now();
            $periodo = \App\Models\PeriodoAcademico::where('activo', true)->first();

            if (!$periodo) {
                $periodo = \App\Models\PeriodoAcademico::where('fecha_inicio', '<=', $fechaDeposito)
                ->where('fecha_fin', '>=', $fechaDeposito)
                ->first();
                
            }

            // 5) Crear el depósito con snapshot de puntos y período
            $deposito = Deposito::create([
                'idBasurero' => 1,
                'idUser' => $user->id,
                'idTipoBasura' => $tipoBasura->idTipoBasura,
                'fechaHora' => $fechaDeposito,
                'idPeriodo' => $periodo ? $periodo->idPeriodo : null,
                'puntos' => $tipoBasura->puntos,
            ]);

            // Obtener el período académico activo para traer puntaje actualizado
            $periodoActivo = \App\Models\PeriodoAcademico::where('activo', true)->first();
            
            // Obtener puntaje total actualizado del período activo
            $puntosActualizados = 0;
            if ($periodoActivo) {
                $puntosActualizados = \App\Models\Puntaje::where('idUser', $user->id)
                    ->where('idPeriodo', $periodoActivo->idPeriodo)
                    ->where('tipo_puntaje','depositos')
                    ->sum('puntos') ?? 0;
            }

            // 6) Actualizar evento como exitoso
            $event->update([    
                'idUser' => $user->id,
                'idTipoBasura' => $tipoBasura->idTipoBasura,
                'idDeposito' => $deposito->idDeposito,
                'status' => 'success',
                'message' => 'Depósito registrado exitosamente',
                'processed_at' => now(),
                'meta' => array_merge($event->meta ?? [], [
                    'resultado' => 'exitoso',
                    'puntos_ganados' => $deposito->puntos ?? $tipoBasura->puntos,
                    'puntos_totales_actuales' => $puntosActualizados,
                    'tipo_basura_encontrado' => $tipoBasura->nombre,
                    'timestamp_fin' => now()->toISOString(),
                    'duracion_ms' => now()->diffInMilliseconds($event->created_at),
                ]),
            ]);

            // 7) Respuesta exitosa
            return response()->json([
                'success' => true,
                'message' => '¡Depósito registrado correctamente!',
                'estudiante' => [
                    'id' => $user->id,
                    'nombre' => $user->nombres,
                    'apellidos' => ($user->primerApellido ?? '') . ' ' . ($user->segundoApellido ?? ''),
                ],
                'deposito' => [
                    'id' => $deposito->idDeposito,
                    'tipo_basura' => $tipoBasura->nombre,
                    'puntos_ganados' => $deposito->puntos ?? $tipoBasura->puntos,
                    'idPeriodo' => $deposito->idPeriodo,
                ],
                'puntaje' => [
                    'puntos_ganados_ahora' => $deposito->puntos ?? $tipoBasura->puntos,
                    'puntos_totales_periodo' => $puntosActualizados,
                    'periodo_activo' => $periodoActivo ? $periodoActivo->nombre : null,
                ],
                'event_id' => $event->id,
            ], 201);

        } catch (\Throwable $e) {
            // Actualizar evento como fallido
            $event->update([
                'status' => 'failed',
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'processed_at' => now(),
                'meta' => array_merge($event->meta ?? [], [
                    'resultado' => 'error',
                    'error_type' => get_class($e),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'timestamp_fin' => now()->toISOString(),
                    'duracion_ms' => now()->diffInMilliseconds($event->created_at),
                ]),
            ]);

            Log::error('Error en RaspberryController::deposito', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'event_id' => $event->id,
                'error_detail' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'error' => $e->getMessage(),
                ] : null,
            ], 500);
        }
    }

    /**
     * Obtiene los eventos recientes de Raspberry Pi para el panel de administración
     */
    public function getEventos(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 20); // Cambiar a paginación
            $page = (int) $request->get('page', 1);
            $status = $request->get('status'); // pending|success|failed


            $query = \App\Models\RaspberryEvent::query()
                ->latest('id');

            if ($status) {
                $query->where('status', $status);
            }

            // Usar paginación en lugar de limit
            $eventos = $query->paginate($perPage, ['*'], 'page', $page);

            // Si no hay eventos, devolver array vacío con paginación
            if ($eventos->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'current_page' => $page,
                    'last_page' => 1,
                    'per_page' => $perPage,
                ]);
            }

            // Cargar relaciones manualmente para evitar problemas
            $eventos->load(['user', 'tipoBasura', 'deposito']);

            return response()->json([
                'success' => true,
                'data' => $eventos->items(),
                'current_page' => $eventos->currentPage(),
                'last_page' => $eventos->lastPage(),
                'per_page' => $eventos->perPage(),
                'total' => $eventos->total(),
                'from' => $eventos->firstItem(),
                'to' => $eventos->lastItem(),
                'eventos' => $eventos->map(function ($evento) {
                    return [
                        'id' => $evento->id,
                        'qr_codigo' => $evento->qr_codigo ?? '',
                        'tipo_basura_nombre' => $evento->tipo_basura_nombre ?? '',
                        'status' => $evento->status ?? 'pending',
                        'message' => $evento->message ?? '',
                        'ip' => $evento->ip ?? '',
                        'created_at' => $evento->created_at ? $evento->created_at->format('Y-m-d H:i:s') : null,
                        'processed_at' => $evento->processed_at ? $evento->processed_at->format('Y-m-d H:i:s') : null,
                        'user' => $evento->user ? [
                            'id' => $evento->user->id,
                            'nombre' => $evento->user->nombres ?? '',
                            'apellidos' => trim(($evento->user->primerApellido ?? '') . ' ' . ($evento->user->segundoApellido ?? '')),
                        ] : null,
                        'tipo_basura' => $evento->tipoBasura ? [
                            'id' => $evento->tipoBasura->idTipoBasura,
                            'nombre' => $evento->tipoBasura->nombre ?? '',
                            'puntos' => $evento->tipoBasura->puntos ?? 0,
                        ] : null,
                        'deposito' => $evento->deposito ? [
                            'id' => $evento->deposito->idDeposito,
                            'puntos' => $evento->deposito->puntos ?? ($evento->tipoBasura ? $evento->tipoBasura->puntos : 0),
                            'fecha' => $evento->deposito->fechaHora ? $evento->deposito->fechaHora->format('Y-m-d H:i:s') : null,
                            'idPeriodo' => $evento->deposito->idPeriodo ?? null,
                        ] : null,
                    ];
                }),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en RaspberryController::getEventos', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener eventos: ' . $e->getMessage(),
                'error_detail' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
                'data' => [],
                'total' => 0,
            ], 500);
        }
    }

    /**
     * Verifica si un estudiante existe por su código QR (sin registrar depósito)
     */
    public function verificarEstudiante(Request $request, $qr_codigo)
    {
        // Verificar si ya existe un evento reciente para evitar spam
        $eventoReciente = RaspberryEvent::where('qr_codigo', $qr_codigo)
            ->where('meta->action', 'verificar_estudiante')
            ->where('created_at', '>=', now()->subMinutes(1)) // Solo en el último minuto
            ->first();

        // Si existe evento reciente, no crear otro
        if ($eventoReciente) {
            // Solo actualizar el timestamp si es necesario
            $eventoReciente->update([
                'meta' => array_merge($eventoReciente->meta ?? [], [
                    'last_check' => now()->toISOString(),
                    'check_count' => ($eventoReciente->meta['check_count'] ?? 1) + 1
                ])
            ]);
            $event = $eventoReciente;
        } else {
            // Crear nuevo evento solo si no hay uno reciente
            $event = RaspberryEvent::create([
                'qr_codigo' => $qr_codigo,
                'status' => 'pending',
                'message' => 'Verificando estudiante',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => [
                    'action' => 'verificar_estudiante',
                    'timestamp_inicio' => now()->toISOString(),
                    'check_count' => 1,
                ],
            ]);
        }

        try {
            // Debug: Log de búsqueda
            \Log::info("Buscando estudiante con QR: {$qr_codigo}");
            
            // Buscar al estudiante con diferentes variaciones
            $user = User::where('qr_codigo', $qr_codigo)->first();
            
            if (!$user) {
                // Buscar sin filtro de rol para debug
                $anyUser = User::where('qr_codigo', $qr_codigo)->first();
                \Log::info("Usuario encontrado sin filtro rol: " . ($anyUser ? "Sí (rol: {$anyUser->rol})" : "No"));
                
                // Buscar similar
                $similar = User::where('qr_codigo', 'LIKE', "%{$qr_codigo}%")->get(['qr_codigo', 'nombres', 'rol']);
                \Log::info("Usuarios similares encontrados: " . $similar->count());
                
                $event->update([
                    'status' => 'failed',
                    'message' => 'Usuario no encontrado - QR: ' . $qr_codigo,
                    'processed_at' => now(),
                    'meta' => array_merge($event->meta ?? [], [
                        'debug_info' => [
                            'searched_qr' => $qr_codigo,
                            'any_user_found' => $anyUser ? true : false,
                            'any_user_role' => $anyUser ? $anyUser->rol : null,
                            'similar_count' => $similar->count(),
                            'similar_codes' => $similar->pluck('qr_codigo')->toArray()
                        ]
                    ])
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'event_id' => $event->id,
                    'debug' => [
                        'searched_qr' => $qr_codigo,
                        'any_user_found' => $anyUser ? true : false,
                        'similar_users' => $similar->map(function($u) {
                            return ['qr' => $u->qr_codigo, 'nombres' => $u->nombres, 'rol' => $u->rol];
                        })
                    ]
                ], 404);
            }
            
            // Verificar que sea estudiante
            if ($user->rol !== 'estudiante') {
                $event->update([
                    'status' => 'failed',
                    'message' => "Usuario encontrado pero no es estudiante (rol: {$user->rol})",
                    'processed_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es estudiante',
                    'event_id' => $event->id,
                ], 403);
            }

            // Obtener el período académico activo
            $periodoActivo = \App\Models\PeriodoAcademico::where('activo', true)->first();
            
            // Obtener puntaje total del período activo
            $puntosActuales = 0;
            if ($periodoActivo) {
                $puntosActuales = \App\Models\Puntaje::where('idUser', $user->id)
                    ->where('idPeriodo', $periodoActivo->idPeriodo)
                    ->sum('puntos') ?? 0;
            }
            
            // Obtener información del curso si está disponible
            $cursoInfo = null;
            if ($user->estudiante && $user->estudiante->cursoParalelo) {
                $cursoParalelo = $user->estudiante->cursoParalelo;
                $cursoInfo = [
                    'curso' => $cursoParalelo->curso->nombre ?? null,
                    'paralelo' => $cursoParalelo->paralelo->nombre ?? null,
                ];
            }

            // Actualizar evento como exitoso
            $event->update([
                'idUser' => $user->id,
                'status' => 'success',
                'message' => 'Estudiante verificado correctamente',
                'processed_at' => now(),
                'meta' => array_merge($event->meta ?? [], [
                    'resultado' => 'exitoso',
                    'estudiante_nombre' => $user->nombres . ' ' . ($user->primerApellido ?? ''),
                    'puntos_actuales' => $puntosActuales,
                    'periodo_activo' => $periodoActivo ? $periodoActivo->nombre : 'Ninguno',
                    'tiene_curso' => !is_null($cursoInfo),
                    'curso_info' => $cursoInfo,
                    'timestamp_fin' => now()->toISOString(),
                    'duracion_ms' => now()->diffInMilliseconds($event->created_at),
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estudiante encontrado',
                'estudiante' => [
                    'id' => $user->id,
                    'nombre' => $user->nombres,
                    'apellidos' => ($user->primerApellido ?? '') . ' ' . ($user->segundoApellido ?? ''),
                    'curso_info' => $cursoInfo,
                    'puntos_actuales' => $puntosActuales,
                    'periodo_activo' => $periodoActivo ? $periodoActivo->nombre : null,
                ],
                'event_id' => $event->id,
            ], 200);

        } catch (\Throwable $e) {
            // Actualizar evento como fallido
            $event->update([
                'status' => 'failed',
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'processed_at' => now(),
                'meta' => array_merge($event->meta ?? [], [
                    'resultado' => 'error',
                    'error_type' => get_class($e),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'timestamp_fin' => now()->toISOString(),
                    'duracion_ms' => now()->diffInMilliseconds($event->created_at),
                ]),
            ]);

            Log::error('Error en RaspberryController::verificarEstudiante', [
                'error' => $e->getMessage(),
                'qr_codigo' => $qr_codigo,
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'event_id' => $event->id,
                'error_detail' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'error' => $e->getMessage(),
                ] : null,
            ], 500);
        }
    }
}
