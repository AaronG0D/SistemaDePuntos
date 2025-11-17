<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'idPeriodo',
        'puntos'
    ];

    protected $casts = [
        'fechaHora' => 'datetime',
        'puntos' => 'integer',
        'puntajeTipoBasura' => 'integer'
    ];

    protected $appends = [
        'puntos_generados'
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

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'idPeriodo', 'idPeriodo');
    }

    // ===== SCOPES =====
    
    /**
     * Scope para filtrar por fecha específica
     */
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fechaHora', $fecha);
    }

    /**
     * Scope para filtrar depósitos recientes (últimos N días)
     */
    public function scopeRecientes($query, $dias = 7)
    {
        return $query->where('fechaHora', '>=', now()->subDays($dias));
    }

    // ===== ACCESSORS =====
    
    /**
     * Accessor para obtener los puntos generados por este depósito
     */
    public function getPuntosGeneradosAttribute()
    {
        // Preferir el snapshot almacenado en el depósito
        if (!is_null($this->puntos)) {
            return (int) $this->puntos;
        }
        // Fallback: puntos actuales del tipo de basura
        return $this->tipoBasura ? (int) $this->tipoBasura->puntos : 0;
    }
}