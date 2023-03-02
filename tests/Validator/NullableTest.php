<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class NullableTest extends TestCase
{
    public function testCanValidateNull(): void
    {
        $validator = new Nullable(new Text(0));
        $this->assertTrue($validator->isValid('text'));
        $this->assertTrue($validator->isValid(null));
        $this->assertFalse($validator->isValid(123));
    }

    public function testCanReturnValidator(): void
    {
        $validator = new Nullable(new Text(0));
        $this->assertTrue($validator->getValidator() instanceof Text);
    }
}
