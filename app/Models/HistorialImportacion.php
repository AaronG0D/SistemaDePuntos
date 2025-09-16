<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialImportacion extends Model
{
    use HasFactory;

    protected $table = 'historial_importaciones';

    protected $fillable = [
        'id_curso_paralelo',
        'insertados',
        'actualizados',
        'omitidos',
        'errores_count'
    ];

    public function cursoParalelo()
    {
        return $this->belongsTo(CursoParalelo::class, 'id_curso_paralelo', 'idCursoParalelo');
    }
}
