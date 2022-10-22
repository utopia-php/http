<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class WhiteListTest extends TestCase
{
    public function testCanValidateStrictly()
    {
        $whiteList = new WhiteList(['string1', 'string2', 3, 4], true);

        $this->assertTrue($whiteList->isValid(3));
        $this->assertTrue($whiteList->isValid(4));
        $this->assertTrue($whiteList->isValid('string1'));
        $this->assertTrue($whiteList->isValid('string2'));

        $this->assertFalse($whiteList->isValid('string3'));
        $this->assertFalse($whiteList->isValid('STRING1'));
        $this->assertFalse($whiteList->isValid('strIng1'));
        $this->assertFalse($whiteList->isValid('3'));
        $this->assertFalse($whiteList->isValid(5));
        $this->assertFalse($whiteList->isArray());
        $this->assertEquals($whiteList->getList(), ['string1', 'string2', 3, 4]);
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $whiteList->getType());
    }

    public function testCanValidateLoosely(): void
    {
        $whiteList = new WhiteList(['string1', 'string2', 3, 4]);

        $this->assertTrue($whiteList->isValid(3));
        $this->assertTrue($whiteList->isValid(4));
        $this->assertTrue($whiteList->isValid('string1'));
        $this->assertTrue($whiteList->isValid('string2'));
        $this->assertTrue($whiteList->isValid('STRING1'));
        $this->assertTrue($whiteList->isValid('strIng1'));
        $this->assertTrue($whiteList->isValid('3'));
        $this->assertTrue($whiteList->isValid('4'));
        $this->assertFalse($whiteList->isValid('string3'));
        $this->assertFalse($whiteList->isValid(5));
        $this->assertEquals($whiteList->getList(), ['string1', 'string2', 3, 4]);

        $whiteList = new WhiteList(['STRING1', 'STRING2', 3, 4]);

        $this->assertTrue($whiteList->isValid(3));
        $this->assertTrue($whiteList->isValid(4));
        $this->assertTrue($whiteList->isValid('string1'));
        $this->assertTrue($whiteList->isValid('string2'));
        $this->assertTrue($whiteList->isValid('STRING1'));
        $this->assertTrue($whiteList->isValid('strIng1'));
        $this->assertTrue($whiteList->isValid('3'));
        $this->assertTrue($whiteList->isValid('4'));
        $this->assertFalse($whiteList->isValid('string3'));
        $this->assertFalse($whiteList->isValid(5));
        $this->assertEquals($whiteList->getList(), ['string1', 'string2', 3, 4]);
    }
}
