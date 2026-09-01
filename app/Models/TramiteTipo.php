<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TramiteTipo extends Model
{
    use HasFactory;

    protected $table = 'tramite_tipos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'costo',
    ];

    protected $casts = [
        'costo' => 'float',
    ];

    /**
     * Un tipo de trámite puede tener muchos trámites asociados.
     */
    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class);
    }

    /**
     * Los requisitos que pertenecen a este tipo de trámite.
     */
    public function requisitos(): BelongsToMany
    {
        return $this->belongsToMany(Requisito::class, 'tramite_tipo_requisitos');
    }
}