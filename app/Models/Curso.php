<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curso extends Model
{
    use SoftDeletes;
    
    protected $table = 'curso';
    protected $primaryKey = 'idCurso';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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