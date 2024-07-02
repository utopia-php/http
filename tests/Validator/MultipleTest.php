<?php

namespace Utopia\Http\Validator;

use PHPUnit\Framework\TestCase;

class MultipleTest extends TestCase
{
    protected Multiple $validator;

    public function setUp(): void
    {
        $this->validator = new Multiple([new Text(20), new URL()], Multiple::TYPE_STRING);
    }

    public function testIsValid()
    {
        $this->assertEquals('string', $this->validator->getType());
        $this->assertEquals("1. Value must be a valid string and at least 1 chars and no longer than 20 chars \n2. Value must be a valid URL \n", $this->validator->getDescription());

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

    public function testRules()
    {
        $validTextValidUrl = 'http://example.com';
        $validTextInvalidUrl = 'hello world';
        $invalidTextValidUrl = 'http://example.com/very-long-url';
        $invalidTextInvalidUrl = 'Some very long text that is also not an URL';

        $vaidator = new Multiple([new Text(20), new URL()], Multiple::TYPE_STRING, Multiple::RULE_ALL);
        $this->assertTrue($vaidator->isValid($validTextValidUrl));
        $this->assertFalse($vaidator->isValid($validTextInvalidUrl));
        $this->assertFalse($vaidator->isValid($invalidTextValidUrl));
        $this->assertFalse($vaidator->isValid($invalidTextInvalidUrl));

        $vaidator = new Multiple([new Text(20), new URL()], Multiple::TYPE_STRING, Multiple::RULE_ANY);
        $this->assertTrue($vaidator->isValid($validTextValidUrl));
        $this->assertTrue($vaidator->isValid($validTextInvalidUrl));
        $this->assertTrue($vaidator->isValid($invalidTextValidUrl));
        $this->assertFalse($vaidator->isValid($invalidTextInvalidUrl));

        $vaidator = new Multiple([new Text(20), new URL()], Multiple::TYPE_STRING, Multiple::RULE_NONE);
        $this->assertFalse($vaidator->isValid($validTextValidUrl));
        $this->assertFalse($vaidator->isValid($validTextInvalidUrl));
        $this->assertFalse($vaidator->isValid($invalidTextValidUrl));
        $this->assertTrue($vaidator->isValid($invalidTextInvalidUrl));
    }
}
