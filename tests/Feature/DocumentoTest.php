<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tramite;
use App\Models\Requisito;
use App\Models\TramiteTipo;
use App\Models\TramiteEstado;
use App\Models\Predio;
use App\Models\Persona;
use App\Models\DocumentoEstado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

class DocumentoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_upload_documento_to_tramite()
    {
        // 1. Setup
        Storage::fake('documentos_locales');
        
        // Crear Usuario Admin
        $role = Role::firstOrCreate(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Crear Datos Base para Trámite
        $predio = Predio::factory()->create();
        $solicitante = Persona::factory()->create();
        
        // Crear Tipo y Estado manualmente si no hay factory, o usar factory si existe.
        // Usaremos create directo para asegurar IDs válidos.
        $tipo = TramiteTipo::create(['nombre' => 'Tramite Test Documento', 'descripcion' => 'Test']);
        $estado = TramiteEstado::create(['nombre' => 'INGRESADO', 'color_ui' => 'primary']);
        
        // Crear estado de documento RECIBIDO (necesario para el controlador)
        DocumentoEstado::firstOrCreate(['nombre' => 'RECIBIDO'], ['color_ui' => 'success']);

        // Crear Trámite
        $tramite = Tramite::create([
            'fecha_conclusion' => null, // Fix: Definir ANTES de estado_id
            'predio_id' => $predio->id,
            'municipio_id' => $predio->municipio_id,
            'usuario_id' => $user->id,
            'solicitante_id' => $solicitante->id,
            'tramite_tipo_id' => $tipo->id,
            'estado_id' => $estado->id,
            'fecha_ingreso' => now(),
            'codigo_acceso' => 'DOC-TEST', // Máximo 8 caracteres
        ]);

        // Crear un Requisito
        $requisito = Requisito::create(['nombre' => 'Carnet de Identidad', 'tramite_tipo_id' => $tipo->id]);

        // Crear Archivo Falso
        $file = UploadedFile::fake()->create('carnet.pdf', 100); // 100KB

        // 2. Actuación
        // Usamos la ruta con prefijo 'admin.'
        $response = $this->actingAs($user)
                         ->post(route('admin.tramites.addDocumento', $tramite), [
                             'requisito_id' => $requisito->id,
                             'documento' => $file,
                         ]);

        // 3. Verificación
        // Verificar redirección y mensaje de éxito
        $response->assertSessionHas('success');
        
        // Verificar que se creó el registro en la BD
        $this->assertDatabaseHas('tramite_documentos', [
            'tramite_id' => $tramite->id,
            'requisito_id' => $requisito->id,
            'nombre_original' => 'carnet.pdf',
        ]);

        // Verificar existencia física en el disco Fake
        $tramite->refresh();
        $documento = $tramite->documentos()->where('requisito_id', $requisito->id)->first();
        
        $this->assertNotNull($documento, 'El documento no se guardó en la base de datos.');
        Storage::disk('documentos_locales')->assertExists($documento->ruta_archivo);
    }
}
