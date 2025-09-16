<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deposito extends Model
{
    use SoftDeletes;
    
    protected $table = 'deposito';
    protected $primaryKey = 'idDeposito';

    protected $fillable = [
        'idBasurero',
        'idUser',
        'idTipoBasura',
        'fechaHora',
        'peso',
        'puntos'
    ];

    protected $casts = [
        'fechaHora' => 'datetime',
        'peso' => 'float',
        'puntos' => 'integer'
    ];

    public function basurero(): BelongsTo
    {
        return $this->belongsTo(Basurero::class, 'idBasurero', 'idBasurero');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUser', 'id');
    }

    public function tipoBasura(): BelongsTo
    {
        return $this->belongsTo(TipoBasura::class, 'idTipoBasura', 'idTipoBasura');
    }
}