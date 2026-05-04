<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tramite;

class PublicConsultaController extends Controller
{
    /**
     * Muestra el formulario de consulta pública.
     */
    public function index()
    {
        return view('public.consulta.index');
    }

    /**
     * Busca el trámite y muestra los resultados.
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'hoja_ruta' => 'required|string',
            'codigo_acceso' => 'required|string',
        ]);

        $tramite = Tramite::where('hoja_ruta', $request->hoja_ruta)
                          ->where('codigo_acceso', $request->codigo_acceso)
                          ->first();

        if (!$tramite) {
            return back()->withInput()->withErrors(['credenciales' => 'La Hoja de Ruta o el Código de Acceso son incorrectos.']);
        }

        $tramite->load(['tipo', 'estado', 'documentos.requisito', 'documentos.estado']);
        
        return view('public.consulta.show', compact('tramite'));
    }
}