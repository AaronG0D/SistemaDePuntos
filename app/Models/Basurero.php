<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Basurero extends Model
{
    use HasFactory;

    protected $table = 'basurero';
    protected $primaryKey = 'idBasurero';

    protected $fillable = [
        'ubicacion',
        'descripcion',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ===== RELACIONES =====
    public function depositos(): HasMany
    {
        return $this->hasMany(Deposito::class, 'idBasurero', 'idBasurero');
    }

    // ===== SCOPES =====
    public function scopeActivos($query)
    {
        return $query->where('estado', 1);
    }

    public function scopeInactivos($query)
    {
        return $query->where('estado', 0);
    }

    // ===== ACCESORS =====
    public function getEstadoTextoAttribute(): string
    {
        return $this->estado ? 'Activo' : 'Inactivo';
    }

    public function getEstadoColorAttribute(): string
    {
        return $this->estado ? 'green' : 'red';
    }

    // ===== MÉTODOS =====
    public function activar(): void
    {
        $this->estado = true;
        $this->save();
    }

    public function desactivar(): void
    {
        $this->estado = false;
        $this->save();
    }
}