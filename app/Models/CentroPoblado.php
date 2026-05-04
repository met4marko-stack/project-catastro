<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentroPoblado extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'centro_poblados'; // Especificar nombre de tabla
    protected $fillable = ['nombre'];
}