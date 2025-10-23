<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Requisito extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /**
     * Los tipos de trámite que requieren este requisito.
     */
    public function tramiteTipos(): BelongsToMany
    {
        return $this->belongsToMany(TramiteTipo::class, 'tramite_tipo_requisitos');
    }
}