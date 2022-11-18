<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class WildcardTest extends TestCase
{
    public function testCanValidateWildcard()
    {
        $validator = new Wildcard();
        $this->assertTrue($validator->isValid([0 => 'string', 1 => 'string']));
        $this->assertTrue($validator->isValid(''));
        $this->assertTrue($validator->isValid([]));
        $this->assertTrue($validator->isValid(1));
        $this->assertTrue($validator->isValid(true));
        $this->assertTrue($validator->isValid(false));
        $this->assertFalse($validator->isArray());
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $validator->getType());
    }
}
