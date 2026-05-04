<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Clickbar\Magellan\Data\Geometries\Polygon;

class Planimetria extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'codigo',
        'fecha_aprobacion',
        'documento_aprobacion',
        'municipio_id',
        'limite_geografico',
        'centro_poblado',
    ];

    /**
     * Define las columnas geoespaciales y su tipo.
     */
    protected $casts = [
        'fecha_aprobacion' => 'date',
        'limite_geografico' => Polygon::class,
    ];

    // --- RELACIONES ---

    /**
     * Una planimetría pertenece a un Municipio.
     */
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    /**
     * Una planimetría puede tener muchos Predios.
     */
    public function predios(): HasMany
    {
        return $this->hasMany(Predio::class);
    }
}