<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionPuntaje extends Model
{
    protected $table = 'asignaciones_puntaje';
    protected $primaryKey = 'idAsignacion';
    
    protected $fillable = [
        'idPuntaje',
        'idPeriodo',
        'idDocente',
        'idMateria',
        'fecha_asignacion',
        'porcentaje',
        'puntos',
        'comentario'
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'porcentaje' => 'decimal:2',
        'puntos' => 'integer'
    ];

    public function puntaje(): BelongsTo
    {
        return $this->belongsTo(Puntaje::class, 'idPuntaje', 'idPuntaje');
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'idPeriodo', 'idPeriodo');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'idDocente', 'idDocente');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'idMateria', 'idMateria');
    }

    /**
     * Scope para obtener asignaciones de un estudiante específico
     */
    public function scopeForStudent($query, $userId)
    {
        return $query->whereHas('puntaje', function($q) use ($userId) {
            $q->where('idUser', $userId);
        });
    }

    /**
     * Scope para obtener asignaciones por período
     */
    public function scopeForPeriod($query, $periodoId)
    {
        return $query->where('idPeriodo', $periodoId);
    }

    /**
     * Scope para obtener asignaciones por materia
     */
    public function scopeForMateria($query, $materiaId)
    {
        return $query->where('idMateria', $materiaId);
    }
}
