<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'tipo_documento' => '2',
            'documento' => $this->faker->numerify('##############'),
            'razao_social' => $this->faker->company(),
            'logradouro' => $this->faker->streetName(),
            'numero' => $this->faker->buildingNumber(),
            'bairro' => 'Centro',
            'codigo_ibge' => '3550308',
            'uf' => 'SP',
            'cep' => $this->faker->numerify('########'),
        ];
    }
}
