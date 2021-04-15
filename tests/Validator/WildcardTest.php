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

class WildcardTest extends TestCase
{
    /**
     * @var Numeric
     */
    protected $assoc;

    public function setUp():void
    {
        $this->assoc = new Wildcard();
    }

    public function tearDown():void
    {
        $this->assoc = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals(true, $this->assoc->isValid([0 => 'string', 1 => 'string']));
        $this->assertEquals(true, $this->assoc->isValid(""));
        $this->assertEquals(true, $this->assoc->isValid([]));
        $this->assertEquals(true, $this->assoc->isValid(1));
        $this->assertEquals(true, $this->assoc->isValid(true));
        $this->assertEquals(true, $this->assoc->isValid(false));
        $this->assertEquals($this->assoc->getType(), \Utopia\Validator::TYPE_STRING);
        $this->assertEquals($this->assoc->isArray(), false);
    }
}
