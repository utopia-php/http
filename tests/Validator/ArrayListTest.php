<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class ArrayListTest extends TestCase
{
    public function testCanValidateTextValues(): void
    {
        $arrayList = new ArrayList(new Text(100));
        $this->assertTrue($arrayList->isArray(), 'true');
        $this->assertTrue($arrayList->isValid([0 => 'string', 1 => 'string']));
        $this->assertTrue($arrayList->isValid(['string', 'string']));
        $this->assertFalse($arrayList->isValid(['string', 'string', 3]));
        $this->assertFalse($arrayList->isValid('string'));
        $this->assertFalse($arrayList->isValid('string'));
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $arrayList->getType());
        $this->assertInstanceOf(Text::class, $arrayList->getValidator());
    }

    public function testCanValidateNumericValues(): void
    {
        $arrayList = new ArrayList(new Numeric());
        $this->assertTrue($arrayList->isValid([1, 2, 3]));
        $this->assertFalse($arrayList->isValid(1));
        $this->assertFalse($arrayList->isValid('string'));
        $this->assertEquals(\Utopia\Validator::TYPE_MIXED, $arrayList->getType());
        $this->assertInstanceOf(Numeric::class, $arrayList->getValidator());
    }

    public function testCanValidateNumericValuesWithBoundaries(): void
    {
        $arrayList = new ArrayList(new Numeric(), 2);
        $this->assertTrue($arrayList->isValid([1]));
        $this->assertTrue($arrayList->isValid([1, 2]));
        $this->assertFalse($arrayList->isValid([1, 2, 3]));
        $this->assertEquals($arrayList->getType(), \Utopia\Validator::TYPE_MIXED);
        $this->assertInstanceOf(Numeric::class, $arrayList->getValidator());
    }
}
