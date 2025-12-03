<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Predio;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PredioHierarchyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_predio_can_have_children()
    {
        // 1. Crear Padre
        $padre = Predio::factory()->create();

        // 2. Crear Hijos vinculados
        $hijo1 = Predio::factory()->create(['inmueble_padre_id' => $padre->id]);
        $hijo2 = Predio::factory()->create(['inmueble_padre_id' => $padre->id]);

        // 3. Crear un predio ajeno (para asegurar que no cuenta de más)
        $ajeno = Predio::factory()->create();

        // 4. Refrescar la instancia del padre para cargar la relación
        $padre->refresh();

        // 5. Verificar relación
        $this->assertCount(2, $padre->hijos);
        $this->assertTrue($padre->hijos->contains($hijo1));
        $this->assertTrue($padre->hijos->contains($hijo2));
        $this->assertFalse($padre->hijos->contains($ajeno));
    }
}
