<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Estudiante extends Model
{
    use SoftDeletes;
    
    protected $table = 'estudiante';
    protected $primaryKey = 'idUser';
    public $incrementing = false; // porque no es autoincremental

    protected $fillable = [
        'idUser',
        'idCursoParalelo'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUser', 'id');
    }

    public function cursoParalelo(): BelongsTo
    {
        return $this->belongsTo(CursoParalelo::class, 'idCursoParalelo', 'idCursoParalelo');
    }

    // ===== Scopes =====
    public function scopeRolEstudiante(Builder $query): Builder
    {
        return $query->whereHas('user', function ($q) {
            $q->where('rol', 'estudiante');
        });
    }

    public function scopeFilterCurso(Builder $query, $idCurso = null): Builder
    {
        if (!$idCurso || $idCurso === 'all') return $query;
        return $query->whereHas('cursoParalelo', function ($q) use ($idCurso) {
            $q->where('idCurso', $idCurso);
        });
    }

    public function scopeFilterParalelo(Builder $query, $idParalelo = null): Builder
    {
        if (!$idParalelo || $idParalelo === 'all') return $query;
        return $query->whereHas('cursoParalelo', function ($q) use ($idParalelo) {
            $q->where('idParalelo', $idParalelo);
        });
    }

    public function scopeOrderByPuntaje(Builder $query, string $direction = 'desc'): Builder
    {
        // Ordena por la suma de puntos del estudiante en la tabla puntaje
        $query->leftJoin('puntaje', 'estudiante.idUser', '=', 'puntaje.idUser')
              ->select('estudiante.*', 'puntaje.puntos as total_puntos')
              ->orderBy('total_puntos', $direction);
        return $query;
    }
} 