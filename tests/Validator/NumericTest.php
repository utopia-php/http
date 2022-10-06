<?php

/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class NumericTest extends TestCase
{
    public function testCanValidateNumerics(): void
    {
        $numeric = new Numeric();

        $this->assertTrue($numeric->isValid('42'));
        $this->assertTrue($numeric->isValid(1337));
        $this->assertTrue($numeric->isValid(0x539));
        $this->assertTrue($numeric->isValid(02471));
        $this->assertTrue($numeric->isValid(1337e0));
        $this->assertTrue($numeric->isValid(9.1));
        $this->assertFalse($numeric->isValid('not numeric'));
        $this->assertFalse($numeric->isValid([]));
        $this->assertFalse($numeric->isArray());
        $this->assertEquals(\Utopia\Validator::TYPE_MIXED, $numeric->getType());
    }
}
