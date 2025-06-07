<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estudiante extends Model
{
    protected $table = 'estudiante';
    protected $primaryKey = 'idUser';
    
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
}