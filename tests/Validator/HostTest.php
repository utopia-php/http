<?php

namespace Utopia\Http\Validator;

use PHPUnit\Framework\TestCase;

class HostTest extends TestCase
{
    protected Host $host;

    public function setUp(): void
    {
        $this->host = new Host(['example.io', 'subdomain.example.test', 'localhost', '*.appwrite.io']);
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->host->isValid('https://example.io/link'), true);
        $this->assertEquals($this->host->isValid('https://localhost'), true);
        $this->assertEquals($this->host->isValid('localhost'), false);
        $this->assertEquals($this->host->isValid('http://subdomain.example.test/path'), true);
        $this->assertEquals($this->host->isValid('http://test.subdomain.example.test/path'), false);
        $this->assertEquals($this->host->isValid('http://appwrite.io/path'), false);
        $this->assertEquals($this->host->isValid('http://me.appwrite.io/path'), true);
        $this->assertEquals($this->host->isValid('http://you.appwrite.io/path'), true);
        $this->assertEquals($this->host->isValid('http://us.together.appwrite.io/path'), true);
        $this->assertEquals($this->host->getType(), 'string');
    }
}
