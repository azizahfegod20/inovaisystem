<?php

namespace Tests\Unit;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DpsNumberConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'cnpj' => '12345678000190',
            'dps_serie' => '00001',
            'dps_next_number' => 1,
            'ambiente' => 2,
            'codigo_ibge' => '3550308',
        ]);
    }

    public function test_dps_number_is_unique_within_transaction(): void
    {
        $numbers = [];

        DB::transaction(function () use (&$numbers) {
            $n1 = $this->getNextDpsNumber($this->company);
            $this->company->update(['dps_next_number' => $n1 + 1]);
            $numbers[] = $n1;

            $n2 = $this->getNextDpsNumber($this->company);
            $this->company->update(['dps_next_number' => $n2 + 1]);
            $numbers[] = $n2;
        });

        $this->assertCount(2, $numbers);
        $this->assertNotSame($numbers[0], $numbers[1]);
    }

    public function test_dps_number_increments_correctly(): void
    {
        DB::transaction(function () {
            $n1 = $this->getNextDpsNumber($this->company);
            $this->company->update(['dps_next_number' => $n1 + 1]);

            $this->company->refresh();
            $this->assertSame(2, $this->company->dps_next_number);

            $n2 = $this->getNextDpsNumber($this->company);
            $this->company->update(['dps_next_number' => $n2 + 1]);

            $this->company->refresh();
            $this->assertSame(3, $this->company->dps_next_number);
        });
    }

    public function test_dps_number_rolls_back_on_failure(): void
    {
        $initialNumber = $this->company->dps_next_number;

        try {
            DB::transaction(function () {
                $n1 = $this->getNextDpsNumber($this->company);
                $this->company->update(['dps_next_number' => $n1 + 1]);

                // Force rollback
                throw new \RuntimeException('Simulated failure');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        $this->company->refresh();
        $this->assertSame($initialNumber, $this->company->dps_next_number);
    }

    protected function getNextDpsNumber(Company $company): int
    {
        $row = DB::table('companies')
            ->where('id', $company->id)
            ->lockForUpdate()
            ->first(['dps_next_number']);

        return (int) $row->dps_next_number;
    }
}
