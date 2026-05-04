<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoEstado extends Model
{
    use HasFactory;

    protected $table = 'documento_estados';

    protected $fillable = [
        'nombre',
        'color_ui',
    ];

    /**
     * Un estado puede estar asociado a muchos documentos de trámite.
     */
    public function tramiteDocumentos(): HasMany
    {
        return $this->hasMany(TramiteDocumento::class, 'estado_id');
    }
}