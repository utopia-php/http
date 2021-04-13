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
    /**
     * @var Numeric
     */
    protected $hexColor = null;

    public function setUp():void
    {
        $this->hexColor = new HexColor();
    }

    public function tearDown():void
    {
        $this->hexColor = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->hexColor->isValid('AB10BC99'), false);
        $this->assertEquals($this->hexColor->isValid('AR1012'), false);
        $this->assertEquals($this->hexColor->isValid('ab12bc99'), false);
        $this->assertEquals($this->hexColor->isValid('00'), false);
        $this->assertEquals($this->hexColor->isValid('ffff'), false);
        $this->assertEquals($this->hexColor->isValid('000'), true);
        $this->assertEquals($this->hexColor->isValid('ffffff'), true);
        $this->assertEquals($this->hexColor->isValid('fff'), true);
        $this->assertEquals($this->hexColor->isValid('000000'), true);
        $this->assertEquals($this->hexColor->getType(), \Utopia\Validator::TYPE_STRING);
        $this->assertEquals($this->hexColor->isArray(), false);
    }
}
