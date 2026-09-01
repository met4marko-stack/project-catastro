<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Provincia;
use App\Models\CentroPoblado;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Clickbar\Magellan\Data\Geometries\MultiPolygon;
use App\Models\Via;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Support\Facades\DB;

class Predio extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'inmueble_padre_id',
        'propiedad_horizontal',
        'numero_unidad',
        'numero_plano',
        'manzano',
        'lote',
        'provincia_id',
        'centro_poblado_id',
        'zona',
        'sup_levantamiento',
        'sup_testimonio',
        'sup_construida',
        'sup_afectada',
        'sup_util',
        'coordenadas',
        'frente_principal',
        'agua_potable',
        'energia_electrica',
        'alcantarillado',
        'alumbrado_publico',
        'gas_domiciliario',
        'id_material_via',
        'via_id',
        'plano_aprobado',
        'forma_lote',
        'fotografia_uno',
        'fotografia_dos',
        'fotografia_tres',
        'fotografia_cuatro',
        'fotografia_cinco',
        'colindante_norte',
        'colindante_sur',
        'colindante_este',
        'colindante_oeste',
        'planimetria_id',
        'municipio_id',
        'numero_matricula_folio',
    ];

    protected $casts = [
        'propiedad_horizontal' => 'boolean',
        'agua_potable' => 'boolean',
        'energia_electrica' => 'boolean',
        'alcantarillado' => 'boolean',
        'alumbrado_publico' => 'boolean',
        'gas_domiciliario' => 'boolean',
        'forma_lote' => 'boolean',
        // CAMBIO: El cast debe ser a MultiPolygon para coincidir con la BD
        'coordenadas' => MultiPolygon::class,
    ];

    // --- RELACIONES ---

    // Relación con la tabla 'vias'
    public function via(): BelongsTo
    {
        return $this->belongsTo(Via::class, 'via_id');
    }

    // Relación con la tabla 'materiales_vias' (asumiendo que crearás este modelo)
    public function materialVia(): BelongsTo
    {
        // Asumiendo que crearás un modelo MaterialVia que corresponde a la tabla 'materiales_vias'
        return $this->belongsTo(MaterialVia::class, 'id_material_via');
    }

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
            ->withPivot('estado_id', 'fecha_inicio', 'fecha_fin')
            ->withTimestamps();
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class);
    }

    public function centroPoblado(): BelongsTo
    {
        return $this->belongsTo(CentroPoblado::class, 'centro_poblado_id');
    }

    public function colindancias(): HasMany
    {
        return $this->hasMany(PredioColindancia::class);
    }

    /**
     * Obtiene el string formateado de colindancias para una orientación dada.
     */
    public function getColindanciaString(string $orientacionNombre): string
    {
        $cols = $this->colindancias()
            ->whereHas('orientacion', fn($q) => $q->where('nombre', $orientacionNombre))
            ->with(['tipoColindante', 'via'])
            ->get();

        if ($cols->isEmpty()) return 'N/A';

        return $cols->map(function ($col) {
            if ($col->tipoColindante->nombre === 'VIA') {
                return $col->via ? $col->via->nombre : $col->nombre_o_numero;
            } elseif ($col->tipoColindante->nombre === 'LOTE') {
                return 'LOTE ' . $col->nombre_o_numero;
            } else {
                // Para OTRO, RIO, etc.
                return $col->tipoColindante->nombre . ' ' . $col->nombre_o_numero;
            }
        })->join(', ');
    }

    public function getCoordenadasWktAttribute()
    {
        if (!$this->id || !$this->attributes['coordenadas']) {
            return null;
        }

        return DB::table('predios')
            ->where('id', $this->id)
            ->selectRaw('ST_AsText(coordenadas) as wkt')
            ->value('wkt');
    }
}
