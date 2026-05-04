<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tramite;
use App\Models\TramiteTipo;
use App\Models\TramiteEstado;
use App\Models\Predio;
use App\Models\Persona;
use App\Models\Municipio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

class TramiteWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_update_status_with_audit_trail()
    {
        // Habilitar auditoría explícitamente para este test
        \Illuminate\Support\Facades\Config::set('audit.console', true);

        // 1. Setup
        $role = Role::firstOrCreate(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $predio = Predio::factory()->create();
        $solicitante = Persona::factory()->create();
        
        // Crear estados
        $estadoIngresado = TramiteEstado::firstOrCreate(['nombre' => 'INGRESADO'], ['color_ui' => 'primary']);
        $estadoObservado = TramiteEstado::firstOrCreate(['nombre' => 'OBSERVADO'], ['color_ui' => 'warning']);
        
        $tipo = TramiteTipo::create(['nombre' => 'Tipo Test', 'descripcion' => 'Test']);

        // Crear Trámite inicial (INGRESADO)
        $tramite = Tramite::create([
            'fecha_conclusion' => null, // Fix del modelo: antes de estado_id
            'predio_id' => $predio->id,
            'municipio_id' => $predio->municipio_id,
            'usuario_id' => $user->id,
            'solicitante_id' => $solicitante->id,
            'tramite_tipo_id' => $tipo->id,
            'estado_id' => $estadoIngresado->id,
            'fecha_ingreso' => now(),
            'codigo_acceso' => 'WORKFLOW',
            'observaciones' => 'Inicio del trámite.',
        ]);

        // 2. Actuación: Cambiar a OBSERVADO
        $justificacion = 'Falta documentación legal.';
        
        $response = $this->actingAs($user)
                         ->put(route('admin.tramites.updateStatus', $tramite), [
                             'estado_id' => $estadoObservado->id,
                             'observaciones' => $justificacion,
                             'fecha_inspeccion' => now()->format('Y-m-d'),
                         ]);

        // 3. Verificación
        $response->assertSessionHas('success');

        // A) Verificar cambio en la BD
        $this->assertDatabaseHas('tramites', [
            'id' => $tramite->id,
            'estado_id' => $estadoObservado->id,
        ]);

        // Verificar que la observación se concatenó (según lógica del controlador)
        $tramite->refresh();
        $this->assertStringContainsString($justificacion, $tramite->observaciones);

        // B) Verificar Traza de Auditoría (Audits)
        // laravel-auditing guarda los cambios en la tabla 'audits'
        $this->assertDatabaseHas('audits', [
            'auditable_type' => Tramite::class,
            'auditable_id' => $tramite->id,
            'event' => 'updated',
            'user_id' => $user->id, // El usuario que hizo el cambio
        ]);
    }

    public function test_cannot_generate_certificate_if_not_approved()
    {
        // 1. Setup
        $role = Role::firstOrCreate(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $municipio = Municipio::first() ?? Municipio::factory()->create();
        $predio = Predio::factory()->create(['municipio_id' => $municipio->id]);
        $solicitante = Persona::factory()->create();
        
        $estadoIngresado = TramiteEstado::firstOrCreate(['nombre' => 'INGRESADO'], ['color_ui' => 'primary']);
        
        // Usamos Tipo 2 (División) asegurando que tenga ID 2, incluso si 'id' no es fillable.
        $tipoDivision = TramiteTipo::find(2);
        if (!$tipoDivision) {
            $tipoDivision = new TramiteTipo();
            $tipoDivision->id = 2;
            $tipoDivision->nombre = 'División de Lotes';
            $tipoDivision->descripcion = 'Test';
            $tipoDivision->save();
        }

        $tramite = Tramite::create([
            'fecha_conclusion' => null,
            'predio_id' => $predio->id,
            'municipio_id' => $predio->municipio_id,
            'usuario_id' => $user->id,
            'solicitante_id' => $solicitante->id,
            'tramite_tipo_id' => 2, // ID explícito
            'estado_id' => $estadoIngresado->id, // NO APROBADO
            'fecha_ingreso' => now(),
            'codigo_acceso' => 'CERT-NO',
        ]);

        // 2. Actuación: Intentar generar certificación
        // La ruta 'generarCertificacionTecnica' redirige a los formularios específicos según el tipo.
        // Para tipo 2 (División), debe redirigir a 'divisionForm'.
        $response = $this->actingAs($user)
                         ->get(route('admin.tramites.generarCertificacion', $tramite));

        // Verificar redirección inicial al formulario de división
        $response->assertRedirect(route('admin.tramites.divisionForm', $tramite));

        // 3. Seguir la redirección manualmente para verificar la validación en el formulario
        $response = $this->actingAs($user)
                         ->get(route('admin.tramites.divisionForm', $tramite));

        // El formulario debe detectar que NO está APROBADO y redirigir al show con errores
        $response->assertRedirect(route('admin.tramites.show', $tramite));
        $response->assertSessionHasErrors();
        
        // Y definitivamente NO deberíamos recibir un PDF (Content-Type application/pdf)
        $this->assertNotEquals('application/pdf', $response->headers->get('content-type'));
    }
}
