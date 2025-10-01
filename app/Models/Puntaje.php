<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Puntaje extends Model
{
    protected $table = 'puntaje';
    protected $primaryKey = 'idPuntaje';
    
    protected $fillable = [
        'idUser',
        'idPeriodo',
        'puntos',
        'fechaAsignacion',
        'comentario',
        'estado',
        'fechaAcumulacion'
    ];

    protected $casts = [
        'fechaAsignacion' => 'datetime',
        'fechaAcumulacion' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUser', 'id');
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'idPeriodo', 'idPeriodo');
    }
}