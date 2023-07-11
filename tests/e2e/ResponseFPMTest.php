<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Tests\E2E\Client;

class ResponseFPMTest extends TestCase
{
    use BaseTest;
    protected Client $client;

    public function setUp(): void
    {
        $this->client = new Client();
    }
}
