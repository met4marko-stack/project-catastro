<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Propietario extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $table = 'propietarios';

    protected $fillable = [
        'persona_id',
        'municipio_id',
        'estado',
    ];

    /**
     * Un propietario corresponde a una Persona.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * Un propietario pertenece a un Municipio.
     */
    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }
}