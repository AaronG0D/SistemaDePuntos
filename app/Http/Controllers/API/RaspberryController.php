<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RaspberryEvent;
use App\Models\User;
use App\Models\TipoBasura;
use App\Models\Deposito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RaspberryController extends Controller
{
    /**
     * Procesa un depósito enviado desde la Raspberry Pi
     */
    public function deposito(Request $request)
    {
        // Validar datos de entrada
        $data = $request->validate([
            'qr_codigo' => ['required', 'string', 'max:255'],
            'tipo_basura' => ['required', 'string', 'max:100'],
            'peso' => ['nullable', 'numeric', 'min:0'], // opcional
        ]);

        // Crear evento inicial en estado "pending"
        $event = RaspberryEvent::create([
            'qr_codigo' => $data['qr_codigo'],
            'tipo_basura_nombre' => $data['tipo_basura'],
            'status' => 'pending',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [
                'raw_request' => $request->all(),
                'peso' => $data['peso'] ?? null,
            ],
        ]);

        try {
            return DB::transaction(function () use ($data, $event, $request) {
                
                // 1) Buscar usuario por código QR
                $user = User::where('qr_codigo', $data['qr_codigo'])->first();

                if (!$user) {
                    $event->update([
                        'status' => 'failed',
                        'message' => 'Usuario no encontrado con el código QR proporcionado',
                        'processed_at' => now(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuario no encontrado'
                    ], 404);
                }

                // Verificar que sea un estudiante
                if (!$user->isStudent()) {
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

                // 2) Buscar tipo de basura
                $tipoBasura = TipoBasura::where(function ($q) use ($data) {
                    $q->where('nombre', 'LIKE', '%' . $data['tipo_basura'] . '%')
                      ->orWhere('descripcion', 'LIKE', '%' . $data['tipo_basura'] . '%');
                })
                ->where('estado', true)
                ->first();

                if (!$tipoBasura) {
                    $event->update([
                        'idUser' => $user->id,
                        'status' => 'failed',
                        'message' => 'Tipo de basura no válido o inactivo',
                        'processed_at' => now(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipo de basura no válido'
                    ], 422);
                }

                // 3) Crear el depósito
                // Nota: Asumiendo que tienes un basurero por defecto o puedes obtenerlo de otra forma
                $deposito = Deposito::create([
                    'idBasurero' => 1, // Ajusta según tu lógica de basureros
                    'idUser' => $user->id,
                    'idTipoBasura' => $tipoBasura->idTipoBasura,
                    'fechaHora' => now(),
                    'puntos' => $tipoBasura->puntos,
                ]);

                // 4) Actualizar evento como exitoso
                $event->update([
                    'idUser' => $user->id,
                    'idTipoBasura' => $tipoBasura->idTipoBasura,
                    'idDeposito' => $deposito->idDeposito,
                    'status' => 'success',
                    'message' => 'Depósito registrado exitosamente',
                    'processed_at' => now(),
                ]);

                // 5) Obtener datos actualizados del estudiante
                $user->refresh();
                
                // Obtener puntos totales del estudiante
                $puntosActuales = $user->depositos()->sum('puntos');
                
                // Obtener información del curso si está disponible
                $cursoInfo = null;
                if ($user->estudiante && $user->estudiante->cursoParalelo) {
                    $cursoParalelo = $user->estudiante->cursoParalelo;
                    $cursoInfo = [
                        'curso' => $cursoParalelo->curso->nombre ?? null,
                        'paralelo' => $cursoParalelo->paralelo->nombre ?? null,
                    ];
                }

                // 6) Respuesta exitosa para la Raspberry
                return response()->json([
                    'success' => true,
                    'message' => '¡Depósito registrado correctamente!',
                    'estudiante' => [
                        'id' => $user->id,
                        'nombre' => $user->nombres,
                        'apellidos' => ($user->primerApellido ?? '') . ' ' . ($user->segundoApellido ?? ''),
                        'curso_info' => $cursoInfo,
                        'puntos_actuales' => $puntosActuales,
                    ],
                    'deposito' => [
                        'id' => $deposito->idDeposito,
                        'tipo_basura' => $tipoBasura->nombre,
                        'puntos_ganados' => $tipoBasura->puntos,
                        'fecha' => $deposito->fechaHora->format('Y-m-d H:i:s'),
                    ],
                    'event_id' => $event->id,
                ], 201);
            });

        } catch (\Throwable $e) {
            // Log del error para debugging
            Log::error('Error en RaspberryController::deposito', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data,
                'event_id' => $event->id,
            ]);

            $event->update([
                'status' => 'failed',
                'message' => 'Error interno del servidor',
                'processed_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtiene los eventos recientes de Raspberry Pi para el panel de administración
     */
    public function getEventos(Request $request)
    {
        try {
            $limit = (int) $request->get('limit', 50);
            $status = $request->get('status'); // pending|success|failed

            // Verificar que la tabla existe
            if (!DB::getSchemaBuilder()->hasTable('raspberry_events')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La tabla raspberry_events no existe. Ejecuta: php artisan migrate',
                    'data' => [],
                    'total' => 0,
                ]);
            }

            $query = \App\Models\RaspberryEvent::query()
                ->latest('id');

            if ($status) {
                $query->where('status', $status);
            }

            $eventos = $query->limit($limit)->get();

            // Si no hay eventos, devolver array vacío
            if ($eventos->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                ]);
            }

            // Cargar relaciones manualmente para evitar problemas
            $eventos->load(['user', 'tipoBasura', 'deposito']);

            return response()->json([
                'success' => true,
                'data' => $eventos->map(function ($evento) {
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
                            'puntos' => $evento->deposito->puntos ?? 0,
                            'fecha' => $evento->deposito->fechaHora ? $evento->deposito->fechaHora->format('Y-m-d H:i:s') : null,
                        ] : null,
                    ];
                }),
                'total' => $eventos->count(),
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
}
