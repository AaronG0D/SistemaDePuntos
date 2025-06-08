<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
} 