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
    protected $range = null;

    public function setUp():void
    {
        $this->range = new Range(0, 5);
    }

    public function tearDown():void
    {
        $this->range = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->range->isValid(0), true);
        $this->assertEquals($this->range->isValid(1), true);
        $this->assertEquals($this->range->isValid(4), true);
        $this->assertEquals($this->range->isValid(5), true);
        $this->assertEquals($this->range->isValid('5'), true);
        $this->assertEquals($this->range->isValid(6), false);
        $this->assertEquals($this->range->isValid(-1), false);
        $this->assertEquals($this->range->getMin(), 0);
        $this->assertEquals($this->range->getMax(), 5);
    }
}
