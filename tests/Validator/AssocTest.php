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

class AssocTest extends TestCase
{
    /**
     * @var Numeric
     */
    protected $assoc;

    public function setUp():void
    {
        $this->assoc = new Assoc();
    }

    public function tearDown():void
    {
        $this->assoc = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals(false, $this->assoc->isValid([0 => 'string', 1 => 'string']));
        $this->assertEquals(false, $this->assoc->isValid(['a']));
        $this->assertEquals(false, $this->assoc->isValid(['a', 'b', 'c']));
        $this->assertEquals(false, $this->assoc->isValid(["0" => 'a', "1" => 'b', "2" => 'c']));
        $this->assertEquals(true, $this->assoc->isValid(["1" => 'a', "0" => 'b', "2" => 'c']));
        $this->assertEquals(true, $this->assoc->isValid(["a" => 'a', "b" => 'b', "c" => 'c']));
        $this->assertEquals(true, $this->assoc->isValid([]));
        $this->assertEquals(true, $this->assoc->isValid(['value' => str_repeat("-", 62000)]));
        $this->assertEquals(false, $this->assoc->isValid(['value' => str_repeat("-", 66000)]));
        $this->assertEquals($this->assoc->getType(), \Utopia\Validator::TYPE_ARRAY);
        $this->assertEquals($this->assoc->isArray(), true);
    }
}
