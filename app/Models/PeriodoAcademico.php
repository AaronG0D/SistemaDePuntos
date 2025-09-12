<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoAcademico extends Model
{
    use HasFactory;

    protected $table = 'periodos_academicos';
    protected $primaryKey = 'idPeriodo';

    protected $fillable = [
        'nombre',
        'codigo',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    // Añadir estos métodos para debug
    protected static function boot()
    {
        parent::boot();
        
        static::updating(function ($periodo) {
            \Log::info('Actualizando período', [
                'id' => $periodo->idPeriodo,
                'original' => $periodo->getOriginal(),
                'changes' => $periodo->getDirty()
            ]);
        });
    }

    public function puntajes(): HasMany
    {
        return $this->hasMany(Puntaje::class, 'idPeriodo', 'idPeriodo');
    }
}
