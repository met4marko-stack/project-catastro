<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tramite extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'predio_id',
        'municipio_id',
        'usuario_id',
        'solicitante_id',
        'tramite_tipo_id',
        'estado_id',
        'hoja_ruta',
        'codigo_acceso',
        'fecha_ingreso',
        'fecha_inspeccion',
        'fecha_conclusion',
        'observaciones',
        'fecha_paralizado',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_inspeccion' => 'date',
        'fecha_conclusion' => 'date',
        'fecha_paralizado' => 'date',
    ];

    // --- RELACIONES ---

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function usuarioRegistra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'solicitante_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TramiteTipo::class, 'tramite_tipo_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(TramiteEstado::class, 'estado_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(TramiteDocumento::class);
    }

    // --- ACCESOR (LÓGICA DE APODERADO) ---

    /**
     * Determina si el trámite fue realizado por un apoderado.
     * Se comporta como si fuera una columna de la base de datos: $tramite->es_realizado_por_apoderado
     */
    public function getEsRealizadoPorApoderadoAttribute(): bool
    {
        // Carga los IDs de persona de los propietarios del predio asociado.
        // Usamos 'Cache' para no repetir esta consulta a la BD si se llama varias veces.
        $propietarioIds = Cache::remember("predio_{$this->predio_id}_propietarios", 60, function () {
            return $this->predio->propietarios->pluck('persona_id')->all();
        });

        // Compara si el ID del solicitante NO está en la lista de IDs de propietarios.
        return !in_array($this->solicitante_id, $propietarioIds);
    }
}