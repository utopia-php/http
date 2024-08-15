<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;
use Utopia\Validator;

class MultipleOfTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function testIsValid()
    {
        $validTextValidUrl = 'http://example.com';
        $validTextInvalidUrl = 'hello world';
        $invalidTextValidUrl = 'http://example.com/very-long-url';
        $invalidTextInvalidUrl = 'Some very long text that is also not an URL';

        $vaidator = new AnyOf([new Text(20), new URL()], Validator::TYPE_STRING);
        $this->assertTrue($vaidator->isValid($validTextValidUrl));
        $this->assertTrue($vaidator->isValid($validTextInvalidUrl));
        $this->assertTrue($vaidator->isValid($invalidTextValidUrl));
        $this->assertFalse($vaidator->isValid($invalidTextInvalidUrl));

        $this->assertCount(2, $vaidator->getValidators());
        $this->assertEquals("Utopia\Validator\Text", \get_class($vaidator->getValidators()[0]));
        $this->assertEquals("Utopia\Validator\URL", \get_class($vaidator->getValidators()[1]));
    }
}
