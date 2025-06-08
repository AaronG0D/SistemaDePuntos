<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paralelo extends Model
{
    protected $table = 'paralelo';
    protected $primaryKey = 'idParalelo';
    
    protected $fillable = [
        'nombre',
        'idCurso'
    ];

    public function cursoParalelos(): HasMany
    {
        return $this->hasMany(CursoParalelo::class, 'idParalelo', 'idParalelo');
    }
}