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

class RangeTest extends TestCase
{
    /**
     * @var Range
     */
    protected $rangeFloat = null;
    protected $rangeInt = null;

    public function setUp():void
    {
        $this->rangeFloat = new Range(0, 1, \Utopia\Validator::TYPE_FLOAT);
        $this->rangeInt = new Range(0, 5, \Utopia\Validator::TYPE_INTEGER);
    }

    public function tearDown():void
    {
        $this->range = null;
    }

    public function testIsValid()
    {
        // Assertions for integer
        $this->assertEquals($this->rangeInt->isValid(0), true);
        $this->assertEquals($this->rangeInt->isValid(1), true);
        $this->assertEquals($this->rangeInt->isValid(4), true);
        $this->assertEquals($this->rangeInt->isValid(5), true);
        $this->assertEquals($this->rangeInt->isValid('5'), true);
        $this->assertEquals($this->rangeInt->isValid('1.5'), false);
        $this->assertEquals($this->rangeInt->isValid(6), false);
        $this->assertEquals($this->rangeInt->isValid(-1), false);
        $this->assertEquals($this->rangeInt->getMin(), 0);
        $this->assertEquals($this->rangeInt->getMax(), 5);
        $this->assertEquals($this->rangeInt->getFormat(), \Utopia\Validator::TYPE_INTEGER);
        $this->assertEquals($this->rangeInt->isArray(), false);
        $this->assertEquals($this->rangeInt->getType(), \Utopia\Validator::TYPE_INTEGER);

        // Assertions for float
        $this->assertEquals($this->rangeFloat->isValid(0.0), true);
        $this->assertEquals($this->rangeFloat->isValid(1.0), true);
        $this->assertEquals($this->rangeFloat->isValid(0.5), true);
        $this->assertEquals($this->rangeFloat->isValid('0.5'), true);
        $this->assertEquals($this->rangeFloat->isValid(4), false);
        $this->assertEquals($this->rangeFloat->isValid('0.6'), true);
        $this->assertEquals($this->rangeFloat->isValid(1.5), false);
        $this->assertEquals($this->rangeFloat->isValid(-1), false);
        $this->assertEquals($this->rangeFloat->getMin(), 0);
        $this->assertEquals($this->rangeFloat->getMax(), 1);
        $this->assertEquals($this->rangeFloat->getFormat(), \Utopia\Validator::TYPE_FLOAT);
        $this->assertEquals($this->rangeFloat->isArray(), false);
        $this->assertEquals($this->rangeFloat->getType(), \Utopia\Validator::TYPE_FLOAT);
    }

    public function testInfinity()
    {
        $integer = new Range(5, INF, \Utopia\Validator::TYPE_INTEGER);
        $float = new Range(-INF, 45.6, \Utopia\Validator::TYPE_FLOAT);

        $this->assertEquals(true, $integer->isValid(25));
        $this->assertEquals(false, $integer->isValid(3));
        $this->assertEquals(true, $integer->isValid(INF));
        $this->assertEquals(true, $float->isValid(32.1));
        $this->assertEquals(false, $float->isValid(97.6));
        $this->assertEquals(true, $float->isValid(-INF));
    }
}
