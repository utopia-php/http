<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class MultipleTest extends TestCase
{
    protected Multiple $validator;

    public function setUp(): void
    {
        $this->validator = new Multiple(new Text(20), new URL());
    }

    public function testIsValid()
    {
        // Valid URL but invalid text length
        $this->assertFalse($this->validator->isValid('http://example.com/very-long-url'));

        // Valid text within length, but invalid URL
        $this->assertFalse($this->validator->isValid('hello world'));

        // Both conditions satisfied
        $this->assertTrue($this->validator->isValid('http://example.com'));
        $this->assertTrue($this->validator->isValid('https://google.com'));

        // Neither condition satisfied
        $this->assertFalse($this->validator->isValid('example.com/hello-world'));
        $this->assertFalse($this->validator->isValid(''));
    }
}
