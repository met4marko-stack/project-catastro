<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany; 
use OwenIt\Auditing\Contracts\Auditable;

class Persona extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'carnet',
        'expedido',
        'ci_fecha_caducidad', 
        'ci_es_indefinido',   
        'telefono',
        'fecha_nacimiento',
    ];

    /**
     * Obtiene el registro de usuario asociado a esta persona.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Una persona puede tener múltiples registros como propietario
     * (ej. uno por cada municipio en el que tiene propiedades).
     */
    public function propietarios(): HasMany
    {
        return $this->hasMany(Propietario::class);
    }

    /**
     * Atributo para obtener el nombre completo.
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->primer_apellido} {$this->segundo_apellido}";
    }
}
