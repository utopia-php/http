<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class NumericWhiteListTest extends TestCase
{
    public function testCanValidateStrictly()
    {
        $whiteList = new NumericWhiteList([1, 2, 3, 4], true);

        $this->assertTrue($whiteList->isValid(3));
        $this->assertTrue($whiteList->isValid(4));

        $this->assertFalse($whiteList->isValid('STRING1'));
        $this->assertFalse($whiteList->isValid('strIng1'));
        $this->assertFalse($whiteList->isValid('3'));
        $this->assertFalse($whiteList->isValid(5));
        $this->assertFalse($whiteList->isArray());
        $this->assertEquals($whiteList->getList(), [1 ,2, 3, 4]);
        $this->assertEquals(\Utopia\Validator::TYPE_INTEGER, $whiteList->getType());
    }

    public function testCanValidateLoosely(): void
    {
        $whiteList = new NumericWhiteList([1, 2, 3, 4]);

        $this->assertTrue($whiteList->isValid(3));
        $this->assertTrue($whiteList->isValid(4));
        $this->assertTrue($whiteList->isValid('3'));
        $this->assertTrue($whiteList->isValid('4'));
        $this->assertFalse($whiteList->isValid('STRING1'));
        $this->assertFalse($whiteList->isValid('strIng1'));
        $this->assertFalse($whiteList->isValid('5'));
        $this->assertEquals($whiteList->getList(), [1, 2, 3, 4]);
    }
}
