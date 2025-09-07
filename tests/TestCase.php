<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run the role and permission seeder for tests
        $this->seed(RolePermissionSeeder::class);
    }
}
