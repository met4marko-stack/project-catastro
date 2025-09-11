<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Municipio;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule; // <-- Importante: Añadir para la validación avanzada
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    /**
     * Muestra la lista de usuarios.
     */
    public function index()
    {
        $user = Auth::user();
        $users = collect();

        if ($user->hasRole('Super-Admin')) {
            $users = User::with(['persona', 'municipio', 'roles'])->get();
        } 
        elseif ($user->hasRole('Admin-Municipal')) {
            $users = User::with(['persona', 'municipio', 'roles'])
                ->where('municipio_id', $user->municipio_id)
                ->get();
        }

        return view('admin.usuarios.index', compact('users'));
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function create()
    {
        $municipios = Municipio::all();
        // --- CAMBIO CLAVE ---
        // Se excluye el rol 'Super-Admin' de la lista para que no se pueda seleccionar.
        $roles = Role::where('name', '!=', 'Super-Admin')->get();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD'];
        
        return view('admin.usuarios.create', compact('municipios', 'roles', 'expedidoOptions'));
    }

    /**
     * Guarda el nuevo usuario en la base de datos.
     */
    public function store(Request $request)
    {
        // Se busca el ID del rol Super-Admin para excluirlo en la validación.
        $superAdminRole = Role::where('name', 'Super-Admin')->first();

        $request->validate([
            // Reglas para la tabla 'personas'
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'carnet' => 'required|string|max:255|unique:personas,carnet',
            'expedido' => 'nullable|string|max:5',
            'telefono' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            
            // Reglas para la tabla 'users'
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            // --- CAMBIO CLAVE ---
            // Se añade una regla para asegurar que el rol seleccionado no sea el de Super-Admin.
            'rol_id' => [
                'required',
                'exists:roles,id',
                Rule::notIn([$superAdminRole->id]),
            ],
            'municipio_id' => Auth::user()->hasRole('Super-Admin') ? 'required|exists:municipios,id' : 'nullable',
        ]);

        try {
            DB::beginTransaction();

            $persona = Persona::create([
                'nombre' => $request->nombre,
                'primer_apellido' => $request->primer_apellido,
                'segundo_apellido' => $request->segundo_apellido,
                'carnet' => $request->carnet,
                'expedido' => $request->expedido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
            ]);

            $municipio_id = Auth::user()->hasRole('Super-Admin') 
                ? $request->municipio_id 
                : Auth::user()->municipio_id;

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'persona_id' => $persona->id,
                'municipio_id' => $municipio_id,
            ]);

            $rol = Role::findById($request->rol_id);
            $user->assignRole($rol);

            DB::commit();

            return redirect()->route('admin.usuarios.index')
                ->with('success', 'Usuario creado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al crear el usuario. ' . $e->getMessage()]);
        }
    }

}

