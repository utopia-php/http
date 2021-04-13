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
    /**
     * @var Numeric
     */
    protected $numeric = null;

    public function setUp():void
    {
        $this->numeric = new Numeric();
    }

    public function tearDown():void
    {
        $this->numeric = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->numeric->isValid('42'), true);
        $this->assertEquals($this->numeric->isValid(1337), true);
        $this->assertEquals($this->numeric->isValid(0x539), true);
        $this->assertEquals($this->numeric->isValid(02471), true);
        $this->assertEquals($this->numeric->isValid(1337e0), true);
        $this->assertEquals($this->numeric->isValid(9.1), true);
        $this->assertEquals($this->numeric->isValid('not numeric'), false);
        $this->assertEquals($this->numeric->isValid([]), false);
        $this->assertEquals($this->numeric->getType(), \Utopia\Validator::TYPE_MIXED);
        $this->assertEquals($this->numeric->isArray(), false);
    }
}
