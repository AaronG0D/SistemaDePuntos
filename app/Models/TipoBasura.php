<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoBasura extends Model
{
    use HasFactory;

    protected $table = 'tipoBasura';
    protected $primaryKey = 'idTipoBasura';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'puntos',
    ];

    protected $casts = [
        'puntos' => 'integer',
    ];

    // ===== RELACIONES =====
    public function depositos(): HasMany
    {
        return $this->hasMany(Deposito::class, 'idTipoBasura', 'idTipoBasura');
    }

    // ===== SCOPES =====
    public function scopeOrdenadosPorPuntos($query)
    {
        return $query->orderBy('puntos', 'desc');
    }

    // ===== ACCESORS =====
    public function getPuntosFormateadosAttribute(): string
    {
        return number_format($this->puntos) . ' pts';
    }

    // ===== MÉTODOS =====
    public function getTotalDepositos(): int
    {
        return $this->depositos()->count();
    }

    public function getTotalPuntosGenerados(): int
    {
        return $this->depositos()->sum('puntos_generados');
    }
} 