<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use App\Models\User;
use App\Models\TramiteEstado;
use App\Models\TramiteTipo;
use App\Models\Municipio;
use Yajra\DataTables\Facades\DataTables;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Cargar auditorías con el usuario y su persona
            $query = Audit::with('user.persona')->select('audits.*')->orderBy('created_at', 'desc');

            return DataTables::eloquent($query)
                ->editColumn('created_at', function ($audit) {
                    return $audit->created_at->format('d/m/Y H:i:s');
                })
                ->editColumn('user_id', function ($audit) {
                    // El modelo User tiene un accesor getNameAttribute que ya maneja la persona
                    return $audit->user ? $audit->user->name : 'Sistema/Desconocido';
                })
                ->filterColumn('user_id', function($query, $keyword) {
                    // Búsqueda personalizada en la tabla personas a través de la relación user
                    $query->whereHas('user.persona', function($q) use ($keyword) {
                        $q->where('nombre', 'ilike', "%{$keyword}%")
                          ->orWhere('primer_apellido', 'ilike', "%{$keyword}%")
                          ->orWhere('segundo_apellido', 'ilike', "%{$keyword}%");
                    });
                })
                ->editColumn('event', function ($audit) {
                    $colors = [
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                    ];
                    $color = $colors[$audit->event] ?? 'secondary';
                    // Traducir eventos comunes
                    $nombres = [
                        'created' => 'Creación',
                        'updated' => 'Actualización',
                        'deleted' => 'Eliminación',
                        'restored' => 'Restauración',
                    ];
                    $nombre = $nombres[$audit->event] ?? ucfirst($audit->event);
                    
                    return '<span class="badge badge-' . $color . '">' . $nombre . '</span>';
                })
                ->editColumn('auditable_type', function ($audit) {
                    // Simplificar el nombre del modelo (App\Models\Predio -> Predio)
                    $modelo = class_basename($audit->auditable_type);
                    return '<strong>' . $modelo . '</strong> <small class="text-muted">#' . $audit->auditable_id . '</small>';
                })
                ->addColumn('resumen_cambios', function ($audit) {
                    if ($audit->event === 'updated') {
                        $cambios = array_keys($audit->new_values);
                        $cambios = array_diff($cambios, ['updated_at']);
                        return implode(', ', $cambios);
                    }
                    return '-';
                })
                ->addColumn('acciones', function ($audit) {
                    $showUrl = route('admin.auditorias.show', $audit->id);
                    return '<a href="' . $showUrl . '" class="btn btn-xs btn-info" title="Ver Detalles"><i class="fas fa-search"></i></a>';
                })
                ->rawColumns(['event', 'auditable_type', 'acciones'])
                ->toJson();
        }

        return view('admin.auditorias.index');
    }

    public function show($id)
    {
        $audit = Audit::with('user')->findOrFail($id);
        
        // Formatear los valores para que sean legibles (IDs -> Nombres)
        $oldValues = $this->formatValues($audit->auditable_type, $audit->old_values);
        $newValues = $this->formatValues($audit->auditable_type, $audit->new_values);
        
        try {
            $auditable = $audit->auditable; 
        } catch (\Exception $e) {
            $auditable = null;
        }

        return view('admin.auditorias.show', compact('audit', 'oldValues', 'newValues', 'auditable'));
    }

    /**
     * Traduce IDs a nombres legibles según el modelo.
     */
    private function formatValues($modelType, $values)
    {
        if (empty($values)) return [];
        
        $formatted = [];
        foreach ($values as $key => $value) {
            $formattedValue = $value;

            // 1. Lógica específica para TRAMITES
            if ($modelType == 'App\Models\Tramite') {
                if ($key == 'estado_id') {
                    $estado = TramiteEstado::find($value);
                    $formattedValue = $estado ? $estado->nombre : $value;
                } elseif ($key == 'tramite_tipo_id') {
                    $tipo = TramiteTipo::find($value);
                    $formattedValue = $tipo ? $tipo->nombre : $value;
                }
            }

            // 2. Lógica genérica para Municipios (común en varios modelos)
            if ($key == 'municipio_id') {
                $muni = Municipio::find($value);
                $formattedValue = $muni ? $muni->nombre : $value;
            }

            // 3. Formato para booleanos
            if (is_bool($value)) {
                $formattedValue = $value ? 'Sí' : 'No';
            }

            // 4. Formato para fechas (si parece fecha)
            // (Opcional, si quieres formatear updated_at, etc.)

            $formatted[$key] = $formattedValue;
        }
        return $formatted;
    }
}
