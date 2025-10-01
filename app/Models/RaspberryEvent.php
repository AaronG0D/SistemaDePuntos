<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaspberryEvent extends Model
{
    protected $table = 'raspberry_events';

    protected $fillable = [
        'qr_codigo',
        'idTipoBasura',
        'tipo_basura_nombre',
        'idUser',
        'idDeposito',
        'status',
        'message',
        'meta',
        'ip',
        'user_agent',
        'processed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'processed_at' => 'datetime',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUser', 'id');
    }

    public function tipoBasura(): BelongsTo
    {
        return $this->belongsTo(TipoBasura::class, 'idTipoBasura', 'idTipoBasura');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'idDeposito', 'idDeposito');
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->latest('id')->limit($limit);
    }
}
