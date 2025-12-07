<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Via extends Model
{
    use HasFactory;

    protected $table = 'vias';

    protected $fillable = [
        'nombre',
        'tipo_via_id',
        'nombre_especifico',
        'municipio_id',
    ];

    protected $with = ['tipoVia'];

    /**
     * Accessor para 'nombre'.
     * Concatena el tipo de vía y el nombre específico.
     */
    protected function nombre(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // Verificamos si tenemos los datos normalizados
                if (!empty($attributes['nombre_especifico']) && $this->tipoVia) {
                    return $this->tipoVia->nombre . ' ' . $attributes['nombre_especifico'];
                }
                // Si no, devolvemos el valor antiguo o el específico solo
                return $value ?? ($attributes['nombre_especifico'] ?? '');
            },
        );
    }

    public function tipoVia(): BelongsTo
    {
        return $this->belongsTo(TipoVia::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }
}
