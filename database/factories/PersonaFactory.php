<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PersonaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'primer_apellido' => $this->faker->lastName(),
            'segundo_apellido' => $this->faker->lastName(),
            'carnet' => $this->faker->unique()->numerify('#######'), // Genera un CI único
            'expedido' => $this->faker->randomElement(['LP', 'CB', 'SC', 'OR', 'PT', 'TJ', 'BE', 'PD', 'CH']),
            // Añade otros campos si tu tabla personas los requiere (ej. telefono, direccion)
        ];
    }
}