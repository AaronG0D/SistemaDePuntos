<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MateriaCursoParalelo extends Model
{
    protected $table = 'materia_curso_paralelo';
    
    protected $fillable = [
        'idMateria',
        'idCursoParalelo'
    ];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'idMateria', 'idMateria');
    }

    public function cursoParalelo(): BelongsTo
    {
        return $this->belongsTo(CursoParalelo::class, 'idCursoParalelo', 'idCursoParalelo');
    }
} 