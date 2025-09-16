<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paralelo extends Model
{
    use SoftDeletes;
    
    protected $table = 'paralelo';
    protected $primaryKey = 'idParalelo';
    
    protected $fillable = [
        'nombre',
        'idCurso',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function cursoParalelos(): HasMany
    {
        return $this->hasMany(CursoParalelo::class, 'idParalelo', 'idParalelo');
    }
}