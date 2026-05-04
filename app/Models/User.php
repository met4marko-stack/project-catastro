<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'email',
        'password',
        'persona_id',
        'municipio_id',
        'google2fa_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    public function getNameAttribute()
    {
        // Si la relación 'persona' existe y está cargada, devuelve su nombre completo
        if ($this->persona) {
            return $this->persona->nombre_completo;
        }

        // Si no hay una persona asociada, devuelve el email como alternativa
        return $this->email;
    }
}
