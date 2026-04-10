<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'cnpj' => $this->faker->numerify('##############'),
            'razao_social' => $this->faker->company(),
            'nome_fantasia' => $this->faker->company(),
            'logradouro' => $this->faker->streetName(),
            'numero' => $this->faker->buildingNumber(),
            'bairro' => 'Centro',
            'codigo_ibge' => '3550308',
            'uf' => 'SP',
            'cep' => $this->faker->numerify('########'),
            'regime_tributario' => 1,
            'reg_esp_trib' => 0,
            'dps_serie' => '00001',
            'dps_next_number' => 1,
            'ambiente' => 2,
        ];
    }
}
