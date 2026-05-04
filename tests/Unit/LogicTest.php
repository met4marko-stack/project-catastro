<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Persona;
use App\Services\PredictionService; // Asegúrate de importar tu servicio

class LogicTest extends TestCase
{
    /**
     * Caso de Prueba 1: Verificar que el Nombre Completo se genera bien.
     * Esto prueba los "Accessors" de tu modelo Persona.
     */
    public function test_nombre_completo_se_genera_correctamente()
    {
        // 1. Preparación: Creamos una persona "falsa" en memoria
        $persona = new Persona([
            'nombre' => 'JUAN',
            'primer_apellido' => 'PEREZ',
            'segundo_apellido' => null // Probamos el caso sin segundo apellido
        ]);

        // 2. Ejecución: Pedimos el atributo calculado
        $nombreCompleto = $persona->nombre_completo; // Laravel usa getNombreCompletoAttribute

        // 3. Aserción (Verificación): Esperamos que sea "JUAN PEREZ" (sin espacios extra)
        $this->assertEquals('JUAN PEREZ', trim($nombreCompleto));
    }

    /**
     * Caso de Prueba 2 (NUEVO): Verificar la lógica de Recomendaciones de IA.
     * Probamos que si la IA detecta "Múltiples propietarios", el sistema sugiera la acción correcta.
     */
    public function test_recomendaciones_ia_generan_consejos_correctos()
    {
        $servicio = new PredictionService();
        
        $factoresDetectados = [
            'Múltiples propietarios (6) - Alta complejidad',
            'Propiedad horizontal'
        ];

        // 2. Ejecución: Generamos las recomendaciones
        $recomendaciones = $servicio->generarRecomendacionesML($factoresDetectados);

        $this->assertContains(
            "Coordinar reunión con todos los propietarios para firmas.", 
            $recomendaciones
        );
        
        $this->assertContains(
            "Solicitar reglamento de condominio y actas de asamblea.", 
            $recomendaciones
        );
    }
}