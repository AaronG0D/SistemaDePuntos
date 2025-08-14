<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    protected $table = 'curso';
    protected $primaryKey = 'idCurso';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    public function paralelos()
    {
        return $this->belongsToMany(Paralelo::class, 'curso_paralelo', 'idCurso', 'idParalelo');
    }

    public function cursoParalelos()
    {
        return $this->hasMany(CursoParalelo::class, 'idCurso');
    }
}