<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Materia extends Model
{
    use SoftDeletes;
    
    protected $table = 'materia';
    protected $primaryKey = 'idMateria';
    
    protected $fillable = [
        'nombre',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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