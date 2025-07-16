<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CursoParalelo extends Model
{
    protected $table = 'curso_paralelo';
    protected $primaryKey = 'idCursoParalelo';
    
    protected $fillable = [
        'idCurso',
        'idParalelo'
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'idCurso', 'idCurso');
    }

    public function paralelo(): BelongsTo
    {
        return $this->belongsTo(Paralelo::class, 'idParalelo', 'idParalelo');
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'idCursoParalelo', 'idCursoParalelo');
    }

    public function docenteMateriaCursos(): HasMany
    {
        return $this->hasMany(DocenteMateriaCurso::class, 'idCursoParalelo', 'idCursoParalelo');
    }

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'materia_curso_paralelo', 'idCursoParalelo', 'idMateria');
    }

    public function materiaCursoParalelos(): HasMany
    {
        return $this->hasMany(MateriaCursoParalelo::class, 'idCursoParalelo', 'idCursoParalelo');
    }
} 