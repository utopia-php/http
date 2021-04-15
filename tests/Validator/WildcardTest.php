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

    protected $wildcard;

    public function setUp():void
    {
        $this->wildcard = new Wildcard();
    }

    public function tearDown():void
    {
        $this->wildcard = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals(true, $this->wildcard->isValid([0 => 'string', 1 => 'string']));
        $this->assertEquals(true, $this->wildcard->isValid(""));
        $this->assertEquals(true, $this->wildcard->isValid([]));
        $this->assertEquals(true, $this->wildcard->isValid(1));
        $this->assertEquals(true, $this->wildcard->isValid(true));
        $this->assertEquals(true, $this->wildcard->isValid(false));
        $this->assertEquals($this->wildcard->getType(), \Utopia\Validator::TYPE_STRING);
        $this->assertEquals($this->wildcard->isArray(), false);
    }
}
