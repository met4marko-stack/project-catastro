<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PredioColindancia extends Model
{
    use HasFactory;

    protected $table = 'predio_colindancias'; // Especificar el nombre de la tabla
    protected $guarded = [];

    public function orientacion() 
    { 
        return $this->belongsTo(Orientacion::class); 
    }

    public function tipoColindante() 
    { 
        return $this->belongsTo(TipoColindante::class); 
    }

    public function via() 
    { 
        return $this->belongsTo(Via::class); 
    }

    public function predio() 
    { 
        return $this->belongsTo(Predio::class); 
    }
}
