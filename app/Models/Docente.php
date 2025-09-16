<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Docente extends Model
{
    use SoftDeletes;
    
    protected $table = 'docente';
    protected $primaryKey = 'idDocente';
    
    protected $fillable = [
        'idUser'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUser', 'id');
    }

    public function docenteMateriaCursos(): HasMany
    {
        return $this->hasMany(DocenteMateriaCurso::class, 'idDocente', 'idDocente');
    }

    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'docente_materia_curso', 'idDocente', 'idMateria');
    }

    public function cursoParalelos()
    {
        return $this->belongsToMany(CursoParalelo::class, 'docente_materia_curso', 'idDocente', 'idCursoParalelo');
    }
}