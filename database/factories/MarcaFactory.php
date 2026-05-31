<?php

namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarcaFactory extends Factory
{
    public function definition()
    {
        $faker = fake('es_ES');
        return [
            'Nombre_Marca' => fake() -> unique()->words(1, true),
            'Estado' => fake() -> boolean(true),
        ];
    }
}