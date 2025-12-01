<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Municipio;
use Illuminate\Support\Facades\DB;

class PlanimetriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            // 'codigo' ahora es string (VARCHAR)
            'codigo' => 'PLAN-' . $this->faker->unique()->numberBetween(100, 999),
            
            'fecha_aprobacion' => $this->faker->date(),
            'documento_aprobacion' => 'Resolución ' . $this->faker->numberBetween(100, 999),
            
            'municipio_id' => Municipio::factory(),
            
            // Campo nuevo añadido en la segunda migración
            'centro_poblado' => 'Centro Poblado ' . $this->faker->city,

            // GEOMETRÍA (SRID 4326 según tu migración original, o ajusta a 32719 si cambiaste)
            // Usamos un polígono simple como límite
            'limite_geografico' => DB::raw("ST_GeomFromText('POLYGON((0 0, 0 10, 10 10, 10 0, 0 0))', 4326)"),
        ];
    }
}