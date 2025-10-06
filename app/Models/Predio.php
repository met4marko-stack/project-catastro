<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Clickbar\Magellan\Data\Geometries\Polygon;

class Predio extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inmueble_padre_id', 'propiedad_horizontal', 'numero_unidad', 'codigo_catastral',
        'numero_plano', 'manzano', 'lote', 'provincia', 'centro_poblado', 'zona',
        'sup_levantamiento', 'sup_testimonio', 'sup_construida', 'sup_afectada', 'sup_util',
        'coordenadas', 'frente_principal', 'agua_potable', 'energia_electrica', 'alcantarillado',
        'alumbrado_publico', 'gas_domiciliario', 'material_via', 'forma_lote',
        'fotografia_uno', 'fotografia_dos', 'fotografia_tres', 'fotografia_cuatro', 'fotografia_cinco',
        'colindante_norte', 'colindante_sur', 'colindante_este', 'colindante_oeste',
        'planimetria_id', 'municipio_id',
    ];

    protected $casts = [
        'propiedad_horizontal' => 'boolean',
        'agua_potable' => 'boolean',
        'energia_electrica' => 'boolean',
        'alcantarillado' => 'boolean',
        'alumbrado_publico' => 'boolean',
        'gas_domiciliario' => 'boolean',
        'forma_lote' => 'boolean',
        'coordenadas' => Polygon::class, // <-- La forma correcta para v2.x
    ];

    // --- RELACIONES ---

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Predio::class, 'inmueble_padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(Predio::class, 'inmueble_padre_id');
    }

    public function planimetria(): BelongsTo
    {
        return $this->belongsTo(Planimetria::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function propietarios(): BelongsToMany
    {
        return $this->belongsToMany(Propietario::class, 'propietarios_predios')
                    ->withPivot('estado', 'fecha_inicio', 'fecha_fin')
                    ->withTimestamps();
    }
}