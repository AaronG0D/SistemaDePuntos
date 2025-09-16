<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaEstudiantesExport;
use App\Imports\EstudiantesImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EstudianteImportController extends Controller
{
    /**
     * Descargar plantilla de estudiantes en formato Excel
     */
    public function descargarPlantilla()
    {
        $export = new PlantillaEstudiantesExport();
        return $export->download('Plantilla_Estudiantes.xlsx');
    }

    /**
     * Importar estudiantes desde archivo Excel
     */
    public function importar(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $archivo = $request->file('archivo');
            $import = new EstudiantesImport();
            $resultados = $import->import($archivo->getPathname());
            
            // Obtener información del curso desde los resultados
            $cursoInfo = null;
            if (isset($resultados['curso_info'])) {
                $cursoInfo = [
                    'curso_nombre' => $resultados['curso_info']['curso'] ?? null,
                    'paralelo_nombre' => $resultados['curso_info']['paralelo'] ?? null,
                ];
            }

            $mensaje = "Importación completada: ";
            $mensaje .= "{$resultados['insertados']} nuevos insertados, ";
            $mensaje .= "{$resultados['actualizados']} actualizados, ";
            $mensaje .= "{$resultados['omitidos']} omitidos, ";
            $mensaje .= count($resultados['errores']) . " errores";

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'insertados' => $resultados['insertados'],
                    'actualizados' => $resultados['actualizados'],
                    'omitidos' => $resultados['omitidos'],
                    'errores_count' => count($resultados['errores']),
                    'estudiantes_afectados' => $resultados['insertados'] + $resultados['actualizados'],
                    'errores' => $resultados['errores'],
                    'mensajes' => $resultados['mensajes'],
                    'curso_info' => $cursoInfo
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
