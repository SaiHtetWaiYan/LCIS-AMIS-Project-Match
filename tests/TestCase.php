<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Decodes a JSON fixture from tests/Fixtures.
     *
     * @return array<string, mixed>
     */
    protected function fixture(string $path): array
    {
        return json_decode(file_get_contents(__DIR__.'/Fixtures/'.$path), true, flags: JSON_THROW_ON_ERROR);
    }
}
