<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    use HasFactory;

    // Especifica el nombre de la tabla si no sigue la convención de Laravel
    protected $table = 'asignaciones';

    // Define los campos que se pueden llenar masivamente
    protected $fillable = [
        'user_id',
        'municipio_id',
        'fecha_asignacion',
        'fecha_cese',
        'estado',
    ];

    /**
     * Define la relación: Una asignación pertenece a un Usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define la relación: Una asignación pertenece a un Municipio.
     */
    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }
}