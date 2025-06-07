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
        'puntajeTotal',
        'fechaActualizacion'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUser', 'id');
    }
}