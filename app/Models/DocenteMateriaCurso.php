<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocenteMateriaCurso extends Model
{
    protected $table = 'docente_materia_curso';
    
    protected $fillable = [
        'idDocente',
        'idMateria',
        'idCursoParalelo'
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'idDocente', 'idDocente');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'idMateria', 'idMateria');
    }

    public function cursoParalelo(): BelongsTo
    {
        return $this->belongsTo(CursoParalelo::class, 'idCursoParalelo', 'idCursoParalelo');
    }
} 