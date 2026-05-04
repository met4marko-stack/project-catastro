<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TramiteEstado extends Model
{
    use HasFactory;

    protected $table = 'tramite_estados';

    protected $fillable = [
        'nombre',
    ];

    /**
     * Un estado puede estar asociado a muchos trámites.
     */
    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class, 'estado_id');
    }
}