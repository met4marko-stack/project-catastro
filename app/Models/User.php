<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- 1. Importar el trait

class User extends Authenticatable
{
    // <-- 2. Usar el trait
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes; 

    // ... el resto de tu modelo se mantiene igual ...
    protected $fillable = [
        'email',
        'password',
        'persona_id',
        'municipio_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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
}

