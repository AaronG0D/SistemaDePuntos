<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    protected $table = 'curso';
    protected $primaryKey = 'idCurso';
    
    protected $fillable = [
        'idCurso',
        'nombre'
    ];

    public function cursoParalelos(): HasMany
    {
        return $this->hasMany(CursoParalelo::class, 'idCurso', 'idCurso');
    }
}