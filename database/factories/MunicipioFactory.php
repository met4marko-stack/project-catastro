<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MunicipioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->city() . ' ' . $this->faker->suffix(),
            // Agrega otros campos obligatorios de tu tabla municipios si los hay
            'departamento' => 'La Paz',
            'logo' => 'default.png',
        ];
    }
}