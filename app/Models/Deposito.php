<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposito extends Model
{
    protected $table = 'deposito';
    protected $primaryKey = 'idDeposito';
    public $timestamps = true;

    protected $fillable = [
        'idUser',
        'idBasurero',
        'idTipoBasura',
        'fechaHora'
    ];

    protected $casts = [
        'fechaHora' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUser', 'id');
    }

    public function basurero(): BelongsTo
    {
        return $this->belongsTo(Basurero::class, 'idBasurero', 'idBasurero');
    }

    public function tipoBasura(): BelongsTo
    {
        return $this->belongsTo(TipoBasura::class, 'idTipoBasura', 'idTipoBasura');
    }
} 