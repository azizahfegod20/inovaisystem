<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost', '127.0.0.1']]);

        $this->withHeaders([
            'Origin' => 'http://localhost',
        ]);
    }
}
