<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materia extends Model
{
    protected $table = 'materia';
    protected $primaryKey = 'idMateria';
    
    protected $fillable = [
        'nombre'
    ];

    public function docenteMateriaCursos(): HasMany
    {
        return $this->hasMany(DocenteMateriaCurso::class, 'idMateria', 'idMateria');
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'docente_materia_curso', 'idMateria', 'idDocente');
    }

    public function cursoParalelos(): BelongsToMany
    {
        return $this->belongsToMany(CursoParalelo::class, 'materia_curso_paralelo', 'idMateria', 'idCursoParalelo');
    }

    public function materiaCursoParalelos(): HasMany
    {
        return $this->hasMany(MateriaCursoParalelo::class, 'idMateria', 'idMateria');
    }
} 