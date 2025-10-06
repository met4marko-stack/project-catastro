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
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use App\Http\Controllers\datatables;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        // Verifica si la petición es para DataTables
        if ($request->ajax()) {
            $userAuth = Auth::user();
            $status = $request->query('status', 'active'); // Por defecto muestra 'activos'

            // Modifica la consulta base según el estado solicitado
            if ($status === 'inactive') {
                $query = User::onlyTrashed(); // Solo usuarios con soft delete
            } else {
                $query = User::query(); // Solo usuarios activos
            }

            // Carga las relaciones necesarias
            $query->with(['persona', 'municipio', 'roles']);

            // Un Admin-Municipal solo ve usuarios de su municipio
            if ($userAuth->hasRole('Admin-Municipal')) {
                $query->where('municipio_id', $userAuth->municipio_id);
            }

            // Excluye siempre al usuario autenticado de la lista
            $query->where('id', '!=', $userAuth->id);

            // Lógica de DataTables (búsqueda, ordenamiento, etc.)
            return datatables()->eloquent($query)
                ->addIndexColumn()
                ->addColumn('nombre_completo', function (User $user) {
                    return $user->persona ? $user->persona->nombre_completo : 'N/A';
                })
                ->editColumn('municipio', function (User $user) {
                    return $user->municipio->nombre ?? 'N/A';
                })
                ->editColumn('roles', function (User $user) {
                    return $user->roles->pluck('name')->map(function ($name) {
                        return '<span class="badge badge-info">' . $name . '</span>';
                    })->implode(' ');
                })
                ->addColumn('estado', function (User $user) {
                    return $user->trashed()
                        ? '<span class="badge badge-danger">Inactivo</span>'
                        : '<span class="badge badge-success">Activo</span>';
                })
                ->addColumn('acciones', function (User $user) {
                    if ($user->trashed()) {
                        $restoreUrl = route('admin.usuarios.restore', $user->id);
                        // Usamos @csrf y @method() directamente en la cadena
                        return '<form action="' . $restoreUrl . '" method="POST" class="d-inline form-restore">
                                ' . csrf_field() . '
                                <button type="submit" class="btn btn-sm btn-info" title="Reactivar"><i class="fas fa-undo"></i></button>
                            </form>';
                    } else {
                        $editUrl = route('admin.usuarios.edit', $user);
                        $deleteUrl = route('admin.usuarios.destroy', $user);
                        // Usamos @csrf y @method() directamente en la cadena
                        return '<a href="' . $editUrl . '" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                            <form action="' . $deleteUrl . '" method="POST" class="d-inline form-delete">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-trash"></i></button>
                            </form>';
                    }
                })
                ->rawColumns(['roles', 'estado', 'acciones']) // Indica a DataTables que estas columnas contienen HTML
                ->toJson();
        }

        // Si no es una petición AJAX, solo muestra la vista
        return view('admin.usuarios.index');
    }

    public function create()
    {
        $municipios = Municipio::all();
        $roles = Role::where('name', '!=', 'Super-Admin')->get();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD', 'QR'];

        return view('admin.usuarios.create', compact('municipios', 'roles', 'expedidoOptions'));
    }

    public function store(Request $request)
    {
        $superAdminRole = Role::where('name', 'Super-Admin')->first();
        $request->validate([
            'nombre' => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            'primer_apellido' => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            'segundo_apellido' => 'nullable|string|max:255|regex:/^[\pL\s\-]+$/u',
            'carnet' => 'required|string|max:255|unique:personas,carnet',
            'expedido' => 'required',
            'ci_fecha_caducidad' => 'nullable|date|required_if:ci_es_indefinido,false',
            'ci_es_indefinido' => 'nullable|boolean',
            'email' => 'required|string|email|max:255|unique:users,email',

            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'telefono' => 'nullable|numeric',

            'rol_id' => ['required', 'exists:roles,id', Rule::notIn([$superAdminRole->id]),],
            'municipio_id' => Auth::user()->hasRole('Super-Admin') ? 'required|exists:municipios,id' : 'nullable',
        ]);

        try {
            DB::beginTransaction();

            $persona = Persona::create([
                'nombre' => Str::upper($request->nombre),
                'primer_apellido' => Str::upper($request->primer_apellido),
                'segundo_apellido' => Str::upper($request->segundo_apellido),
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
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD', 'QR'];
        return view('admin.usuarios.edit', compact('usuario', 'municipios', 'roles', 'expedidoOptions'));
    }

    public function update(Request $request, User $usuario)
    {
        $superAdminRole = Role::where('name', 'Super-Admin')->first();
        $request->validate([
            'nombre' => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            'primer_apellido' => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            'segundo_apellido' => 'nullable|string|max:255|regex:/^[\pL\s\-]+$/u',
            'carnet' => 'required|string|max:255|unique:personas,carnet,' . $usuario->persona_id,
            'expedido' => 'required',
            'ci_fecha_caducidad' => 'nullable|date|required_if:ci_es_indefinido,false',
            'ci_es_indefinido' => 'nullable|boolean',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'rol_id' => ['required', 'exists:roles,id', Rule::notIn([$superAdminRole->id])],
            'municipio_id' => Auth::user()->hasRole('Super-Admin') ? 'required|exists:municipios,id' : 'nullable',
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'telefono' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $usuario->persona->update([
                'nombre' => Str::upper($request->nombre),
                'primer_apellido' => Str::upper($request->primer_apellido),
                'segundo_apellido' => Str::upper($request->segundo_apellido),
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
