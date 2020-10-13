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

class MultipleTest extends TestCase
{
    /**
     * @var Multiple
     */
    protected $multiple = null;

    public function setUp():void
    {
        $this->multiple = new Multiple(new Range(10, 20));
        $this->multiple->addRule(new Numeric());
    }

    public function tearDown():void
    {
        $this->multiple = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->multiple->isValid('42'), false);
        $this->assertEquals($this->multiple->isValid(20), true);
        $this->assertEquals($this->multiple->isValid(1), false);
        $this->assertEquals($this->multiple->isValid(-1), false);
        $this->assertEquals($this->multiple->isValid('-1'), false);
    }
}
