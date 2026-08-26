<?php

namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_application_boots(): void
    {
        $this->assertTrue(true);
    }

    public function test_testing_environment_is_configured(): void
    {
        $this->assertSame('testing', config('app.env'));
    }
}
