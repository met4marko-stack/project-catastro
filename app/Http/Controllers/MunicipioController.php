<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MunicipioController extends Controller
{
    /**
     * Muestra la lista de municipios.
     */
    public function index()
    {
        $municipios = Municipio::all();
        // Array con los 9 departamentos de Bolivia
        $departamentos = [
            'Beni',
            'Chuquisaca',
            'Cochabamba',
            'La Paz',
            'Oruro',
            'Pando',
            'Potosí',
            'Santa Cruz',
            'Tarija',
        ];
        return view('admin.municipios.index', compact('municipios', 'departamentos'));
    }

    /**
     * Guarda un nuevo municipio en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:municipios,nombre',
            'departamento' => 'required|string|max:255',
            // --- CAMBIO CLAVE ---
            // Ahora el logo es obligatorio al crear
            'logo' => 'required|image|max:2048', // 2MB Max
        ]);

        $data = $request->only('nombre', 'departamento');

        // Como el logo es requerido, ya no necesitamos el if()
        $data['logo'] = $request->file('logo')->store('logos', 'public');

        Municipio::create($data);

        return redirect()->route('admin.municipios.index')
            ->with('success', 'Municipio creado exitosamente.');
    }

    /**
     * Actualiza un municipio existente.
     */
    public function update(Request $request, Municipio $municipio)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:municipios,nombre,' . $municipio->id,
            'departamento' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        $data = $request->only('nombre', 'departamento');

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($municipio->logo) {
                Storage::disk('public')->delete($municipio->logo);
            }
            // Guardar el nuevo logo
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $municipio->update($data);

        return redirect()->route('admin.municipios.index')
            ->with('success', 'Municipio actualizado exitosamente.');
    }

    /**
     * Elimina un municipio.
     */
    public function destroy(Municipio $municipio)
    {
        // Eliminar logo si existe
        if ($municipio->logo) {
            Storage::disk('public')->delete($municipio->logo);
        }

        $municipio->delete();

        return redirect()->route('admin.municipios.index')
            ->with('success', 'Municipio eliminado exitosamente.');
    }
}

