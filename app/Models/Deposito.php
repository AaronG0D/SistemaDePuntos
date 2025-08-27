<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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

    // Incluir atributos calculados al serializar
    protected $appends = ['puntos_generados'];

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

    // ---- Local Scopes ----
    public function scopePorFecha(Builder $query, $fecha): Builder
    {
        return $query->whereDate('fechaHora', $fecha);
    }

    public function scopeRecientes(Builder $query, int $dias): Builder
    {
        return $query->where('fechaHora', '>=', now()->subDays($dias));
    }

    // ---- Accessors ----
    public function getPuntosGeneradosAttribute(): int
    {
        // Evita consultas extra si ya está cargada la relación
        if ($this->relationLoaded('tipoBasura')) {
            return (int) ($this->tipoBasura->puntos ?? 0);
        }
        // Fallback minimal si no está cargada
        return 0;
    }
} 