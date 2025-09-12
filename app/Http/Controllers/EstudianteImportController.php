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
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB máximo
        ]);

        try {
            $archivo = $request->file('archivo');
            
            // Crear instancia del import
            $import = new EstudiantesImport();
            
            // Procesar el archivo directamente
            $resultados = $import->import($archivo->getPathname());
            
            // Crear mensaje detallado basado en los resultados
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
                    'mensajes' => $resultados['mensajes']
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
