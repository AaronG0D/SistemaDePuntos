<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuario';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombres',
        'primerApellido',
        'segundoApellido',
        'email',
        'rol',
        'qr_codigo',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasRole(string $role): bool
    {
        return $this->rol === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('administrador');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('docente');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('estudiante');
    }

    public function estudiante(): HasOne
    {
        return $this->hasOne(Estudiante::class, 'idUser', 'id');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'idUser', 'id');
    }
    public function puntaje()
    {
        return $this->hasOne(\App\Models\Puntaje::class, 'idUser', 'id');
    }

    public function depositos()
    {
        return $this->hasMany(Deposito::class, 'idUser', 'id');
    }
}
