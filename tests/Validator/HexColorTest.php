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

class HexColorTest extends TestCase
{
    public function testCanValidateHexColor()
    {
        $hexColor = new HexColor();
        $this->assertTrue($hexColor->isValid('000'));
        $this->assertTrue($hexColor->isValid('ffffff'));
        $this->assertTrue($hexColor->isValid('fff'));
        $this->assertTrue($hexColor->isValid('000000'));

        $this->assertFalse($hexColor->isValid('AB10BC99'));
        $this->assertFalse($hexColor->isValid('AR1012'));
        $this->assertFalse($hexColor->isValid('ab12bc99'));
        $this->assertFalse($hexColor->isValid('00'));
        $this->assertFalse($hexColor->isValid('ffff'));
        $this->assertFalse($hexColor->isArray());

        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $hexColor->getType());
    }
}
