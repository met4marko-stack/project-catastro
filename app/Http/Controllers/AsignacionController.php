<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use Illuminate\Http\Request;

class AsignacionController extends Controller
{
    /**
     * Muestra la página principal del historial de asignaciones.
     */
    public function index()
    {
        // Obtiene todas las asignaciones, cargando la información relacionada del usuario,
        // la persona y el municipio para evitar consultas N+1.
        $asignaciones = Asignacion::with(['user.persona', 'municipio'])->get();

        // Envía los datos a la vista
        return view('admin.asignaciones.index', compact('asignaciones'));
    }
}