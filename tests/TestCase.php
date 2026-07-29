<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        if (Schema::hasTable('roles')) {
            $this->seed(AuthorizationSeeder::class);
            $this->seed(SystemSettingSeeder::class);
        }
    }
}
