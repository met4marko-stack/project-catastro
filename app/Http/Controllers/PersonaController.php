<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PersonaController extends Controller
{
    /**
     * Almacena una nueva persona vía AJAX y devuelve sus datos.
     */
    public function storeAjax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255', // Permite que sea opcional
            'carnet' => 'nullable|string|max:255|unique:personas,carnet',
            'expedido' => 'nullable|string|max:2', // Validar que sea un código de 2 letras
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $persona = Persona::create([
            'nombre' => Str::upper($request->nombre),
            'primer_apellido' => Str::upper($request->primer_apellido),
            'segundo_apellido' => Str::upper($request->segundo_apellido),
            'carnet' => $request->carnet,
            'expedido' => Str::upper($request->expedido),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Persona registrada exitosamente.',
            'persona' => [
                'id' => $persona->id,
                'nombre_completo' => $persona->nombre_completo . ' (' . $persona->carnet . ')'
            ]
        ]);
    }

    /**
     * Busca personas para el autocompletado de Select2 (AJAX).
     */
    public function searchAjax(Request $request)
    {
        $term = $request->input('term', '');

        $personas = Persona::where(function ($query) use ($term) {
            $query->where('nombre', 'ILIKE', "%$term%")
                ->orWhere('primer_apellido', 'ILIKE', "%$term%")
                ->orWhere('carnet', 'ILIKE', "%$term%");
        })
            ->limit(20)
            ->get();

        // Formatear para Select2
        $results = $personas->map(function ($persona) {
            return [
                'id' => $persona->id,
                'text' => $persona->nombre_completo . ' (' . $persona->carnet . ')'
            ];
        });

        return response()->json(['results' => $results]);
    }
}
