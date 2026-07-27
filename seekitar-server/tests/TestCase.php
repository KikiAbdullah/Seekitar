<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshesDatabase::class, class_uses_recursive($this), true)) {
            $this->setUpRefreshesDatabase();
        }
    }

    protected function tearDown(): void
    {
        if (in_array(RefreshesDatabase::class, class_uses_recursive($this), true)) {
            $this->tearDownRefreshesDatabase();
        }

        parent::tearDown();
    }
}
