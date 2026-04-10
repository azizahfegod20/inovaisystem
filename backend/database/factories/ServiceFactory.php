<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'codigo_lc116' => '01.01',
            'descricao' => $this->faker->sentence(),
            'aliquota_iss' => 0.0500,
            'is_favorite' => false,
        ];
    }
}
