<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Municipio;
use App\Models\Persona;
use App\Models\Asignacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class UsuarioController extends Controller
{
    // ... index, create, store, edit, update, destroy methods remain the same ...
    public function index()
    {
        $user = Auth::user();
        $users = collect();

        if ($user->hasRole('Super-Admin')) {
            // Se usa withTrashed() para mostrar también los usuarios inactivos
            $users = User::withTrashed()->with(['persona', 'municipio', 'roles'])->get();
        } elseif ($user->hasRole('Admin-Municipal')) {
            $users = User::withTrashed()->with(['persona', 'municipio', 'roles'])
                ->where('municipio_id', $user->municipio_id)
                ->get();
        }

        return view('admin.usuarios.index', compact('users'));
    }

    public function create()
    {
        $municipios = Municipio::all();
        $roles = Role::where('name', '!=', 'Super-Admin')->get();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD'];

        return view('admin.usuarios.create', compact('municipios', 'roles', 'expedidoOptions'));
    }

    public function store(Request $request)
    {
        $superAdminRole = Role::where('name', 'Super-Admin')->first();
        $request->validate([
            'nombre' => 'required|string|max:255',
            'carnet' => 'required|string|max:255|unique:personas,carnet',
            // --- VALIDACIÓN AÑADIDA ---
            'ci_fecha_caducidad' => 'nullable|date|required_if:ci_es_indefinido,false',
            'ci_es_indefinido' => 'nullable|boolean',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'rol_id' => ['required', 'exists:roles,id', Rule::notIn([$superAdminRole->id]),],
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
                // --- DATOS AÑADIDOS ---
                'ci_es_indefinido' => $request->has('ci_es_indefinido'),
                'ci_fecha_caducidad' => $request->has('ci_es_indefinido') ? null : $request->ci_fecha_caducidad,
            ]);

            $municipio_id = Auth::user()->hasRole('Super-Admin') ? $request->municipio_id : Auth::user()->municipio_id;

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'persona_id' => $persona->id,
                'municipio_id' => $municipio_id,
            ]);

            $rol = Role::findById($request->rol_id);
            $user->assignRole($rol);

            Asignacion::create([
                'user_id' => $user->id,
                'municipio_id' => $municipio_id,
                'fecha_asignacion' => Carbon::now(),
                'estado' => 'Activo',
            ]);

            DB::commit();
            return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al crear el usuario. ' . $e->getMessage()]);
        }
    }

    public function edit(User $usuario)
    {
        $municipios = Municipio::all();
        $roles = Role::where('name', '!=', 'Super-Admin')->get();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD'];
        return view('admin.usuarios.edit', compact('usuario', 'municipios', 'roles', 'expedidoOptions'));
    }

    public function update(Request $request, User $usuario)
    {
        $superAdminRole = Role::where('name', 'Super-Admin')->first();
        $request->validate([
            'nombre' => 'required|string|max:255',
            'carnet' => 'required|string|max:255|unique:personas,carnet,' . $usuario->persona_id,
            // --- VALIDACIÓN AÑADIDA ---
            'ci_fecha_caducidad' => 'nullable|date|required_if:ci_es_indefinido,false',
            'ci_es_indefinido' => 'nullable|boolean',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'rol_id' => ['required', 'exists:roles,id', Rule::notIn([$superAdminRole->id])],
            'municipio_id' => Auth::user()->hasRole('Super-Admin') ? 'required|exists:municipios,id' : 'nullable',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        try {
            DB::beginTransaction();

            $usuario->persona->update([
                'nombre' => $request->nombre,
                'primer_apellido' => $request->primer_apellido,
                'segundo_apellido' => $request->segundo_apellido,
                'carnet' => $request->carnet,
                'expedido' => $request->expedido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                // --- DATOS AÑADIDOS ---
                'ci_es_indefinido' => $request->has('ci_es_indefinido'),
                'ci_fecha_caducidad' => $request->has('ci_es_indefinido') ? null : $request->ci_fecha_caducidad,
            ]);

            $usuario->email = $request->email;
            if ($request->filled('password')) {
                $usuario->password = Hash::make($request->password);
            }
            if (Auth::user()->hasRole('Super-Admin') && $usuario->municipio_id != $request->municipio_id) {
                Asignacion::where('user_id', $usuario->id)->where('estado', 'Activo')->update(['fecha_cese' => Carbon::now(), 'estado' => 'Inactivo']);
                Asignacion::create(['user_id' => $usuario->id, 'municipio_id' => $request->municipio_id, 'fecha_asignacion' => Carbon::now(), 'estado' => 'Activo']);
                $usuario->municipio_id = $request->municipio_id;
            }
            $usuario->save();
            $rol = Role::findById($request->rol_id);
            $usuario->syncRoles([$rol]);

            DB::commit();
            return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al actualizar el usuario. ' . $e->getMessage()]);
        }
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id == Auth::id()) {
            return redirect()->route('admin.usuarios.index')->withErrors(['error' => 'No puedes desactivar tu propia cuenta.']);
        }
        try {
            DB::beginTransaction();
            Asignacion::where('user_id', $usuario->id)->where('estado', 'Activo')->update(['fecha_cese' => Carbon::now(), 'estado' => 'Inactivo',]);
            $usuario->delete();
            DB::commit();
            return redirect()->route('admin.usuarios.index')->with('success', 'Usuario desactivado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.usuarios.index')->withErrors(['error' => 'Ocurrió un error al desactivar el usuario.']);
        }
    }

    /**
     * Restaura un usuario desactivado (soft deleted).
     */
    public function restore($id)
    {
        $usuario = User::withTrashed()->findOrFail($id);
        try {
            DB::beginTransaction();
            $usuario->restore();
            Asignacion::create(['user_id' => $usuario->id, 'municipio_id' => $usuario->municipio_id, 'fecha_asignacion' => Carbon::now(), 'estado' => 'Activo',]);
            DB::commit();
            return redirect()->route('admin.usuarios.index')->with('success', 'Usuario reactivado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.usuarios.index')->withErrors(['error' => 'Ocurrió un error al reactivar el usuario.']);
        }
    }
}
