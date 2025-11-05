<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TramiteDocumento extends Model
{
    use HasFactory;

    protected $table = 'tramite_documentos';

    protected $fillable = [
        'tramite_id',
        'requisito_id',
        'estado_id',
        'user_id',
        'ruta_archivo',
        'nombre_original',
        'observaciones',
    ];

    // --- RELACIONES ---

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    public function requisito(): BelongsTo
    {
        return $this->belongsTo(Requisito::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(DocumentoEstado::class, 'estado_id');
    }
}