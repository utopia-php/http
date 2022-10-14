<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class IntegerTest extends TestCase
{
    public function testCanValidateStrictly()
    {
        $validator = new Integer();
        $this->assertTrue($validator->isValid(23));
        $this->assertFalse($validator->isValid('23'));
        $this->assertFalse($validator->isValid(23.5));
        $this->assertFalse($validator->isValid('23.5'));
        $this->assertFalse($validator->isValid(null));
        $this->assertFalse($validator->isValid(true));
        $this->assertFalse($validator->isValid(false));
        $this->assertFalse($validator->isArray());
        $this->assertEquals(\Utopia\Validator::TYPE_INTEGER, $validator->getType());
    }

    public function testCanValidateLoosely()
    {
        $validator = new Integer(true);
        $this->assertTrue($validator->isValid(23));
        $this->assertTrue($validator->isValid('23'));
        $this->assertFalse($validator->isValid(23.5));
        $this->assertFalse($validator->isValid('23.5'));
        $this->assertFalse($validator->isValid(null));
        $this->assertFalse($validator->isValid(true));
        $this->assertFalse($validator->isValid(false));
        $this->assertFalse($validator->isArray());
        $this->assertEquals(\Utopia\Validator::TYPE_INTEGER, $validator->getType());
    }
}
