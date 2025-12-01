<?php

namespace Database\Factories;

use App\Models\Municipio;
use App\Models\Planimetria; // <-- ASEGÚRATE DE QUE ESTO ESTÉ AQUÍ
use App\Models\Predio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class PredioFactory extends Factory
{
    protected $model = Predio::class;

    public function definition(): array
    {
        return [
            'numero_matricula_folio' => $this->faker->unique()->numerify('#######/202#'),
            'municipio_id' => Municipio::factory(),
            
            // --- ESTA ES LA LÍNEA QUE FALTA O FALLA ---
            'planimetria_id' => Planimetria::factory(),
            // -----------------------------------------

            'codigo_catastral' => $this->faker->unique()->numerify('01-0#-###-####'),
            'manzano' => $this->faker->numerify('M-##'),
            'lote' => $this->faker->numerify('L-##'),
            'sup_levantamiento' => $this->faker->randomFloat(2, 150, 1000),
            'sup_testimonio' => $this->faker->randomFloat(2, 150, 1000),
            'propiedad_horizontal' => false,
            'agua_potable' => $this->faker->boolean(),
            'energia_electrica' => $this->faker->boolean(),
            'alcantarillado' => $this->faker->boolean(),
            'alumbrado_publico' => $this->faker->boolean(),
            'gas_domiciliario' => $this->faker->boolean(),

            // Coordenadas con SRID 32719 y 4 dimensiones (XYZM)
            'coordenadas' => DB::raw("ST_GeomFromText('MULTIPOLYGON(((0 0 0 0, 0 10 0 0, 10 10 0 0, 10 0 0 0, 0 0 0 0)))', 32719)"),
        ];
    }
}