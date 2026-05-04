<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule; // Importar Rule para validación unique ignorando el propio ID
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon; // Para formatear la fecha

class ProfileController extends Controller
{
    /**
     * Muestra el formulario para editar el perfil del usuario autenticado.
     */
    public function edit()
    {
        $user = Auth::user(); 
        // Asegúrate de cargar la relación 'persona'
        $user->load('persona'); 
        
        return view('admin.profile.edit', compact('user'));
    }

    /**
     * Actualiza el perfil y los datos de la persona del usuario autenticado.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        // Asegúrate de tener la relación 'persona' cargada para acceder a ella
        $user->load('persona'); 

        // --- Reglas de Validación ---
        $rules = [
            // Datos del User
            'email' => [
                'required', 
                'string', 
                'email', 
                'max:255', 
                // Valida que el email sea único, ignorando el email actual del usuario
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|min:8|confirmed', // 'confirmed' busca 'password_confirmation'

            // Datos de la Persona
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'nullable|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            // Valida que el carnet sea único en la tabla personas, ignorando el de la persona actual
            'carnet' => [
                'nullable', 
                'string', 
                'max:255',
                Rule::unique('personas')->ignore($user->persona->id),
            ],
            'expedido' => 'nullable|string|max:5',
            'telefono' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            // 'ci_fecha_caducidad' es opcional, lo puedes añadir si lo necesitas en el formulario
            // 'ci_es_indefinido' es booleano, se gestiona con un checkbox
            'ci_es_indefinido' => 'boolean', 
        ];

        // --- Mensajes de error personalizados ---
        $messages = [
            // User
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo electrónico ya está en uso por otro usuario.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            
            // Persona
            'nombre.required' => 'El nombre es obligatorio.',
            'carnet.required' => 'El carnet de identidad es obligatorio.',
            'carnet.unique' => 'Este carnet de identidad ya está registrado por otra persona.',
            'expedido.required' => 'El campo expedido es obligatorio.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
        ];

        // Validamos la solicitud
        $request->validate($rules, $messages);

        // --- Actualizar datos del User ---
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save(); // Guardamos los cambios del User

        // --- Actualizar datos de la Persona relacionada ---
        $persona = $user->persona; // Accedemos a la persona relacionada

        if ($persona) { // Aseguramos que la relación exista
            $persona->nombre = $request->nombre;
            $persona->primer_apellido = $request->primer_apellido;
            $persona->segundo_apellido = $request->segundo_apellido;
            $persona->carnet = $request->carnet;
            $persona->expedido = $request->expedido;
            $persona->telefono = $request->telefono;
            $persona->fecha_nacimiento = $request->fecha_nacimiento; // Ya es nullable en la DB
            $persona->ci_es_indefinido = $request->has('ci_es_indefinido'); // Para checkbox
            // Si tuvieras 'ci_fecha_caducidad' en el formulario:
            // $persona->ci_fecha_caducidad = $request->ci_fecha_caducidad; 
            
            $persona->save(); // Guardamos los cambios de la Persona
        }


        // Redirigimos con un mensaje de éxito
        return redirect()->route('admin.profile.edit')
                         ->with('status', '¡Perfil y datos personales actualizados con éxito!');
    }
}