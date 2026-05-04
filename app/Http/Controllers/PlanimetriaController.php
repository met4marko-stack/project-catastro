<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlanimetriaController extends Controller
{
    /**
     * Muestra la vista con el mapa de Leaflet.
     */
    public function visualizacion()
    {
        // Simplemente retorna la vista que contiene el mapa de Leaflet
        // que creamos en el paso anterior.
        return view('admin.planimetrias.visualizacion');
    }
}
