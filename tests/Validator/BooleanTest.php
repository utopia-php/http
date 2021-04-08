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

class BooleanTest extends TestCase
{
    /**
     * @var Boolean
     */
    protected $boolean;

    public function setUp():void
    {
    }

    public function tearDown():void
    {
        $this->boolean = null;
    }

    public function testIsValid()
    {
        $this->boolean = new Boolean();

        $this->assertEquals(true, $this->boolean->isValid(true));
        $this->assertEquals(true, $this->boolean->isValid(false));
        $this->assertEquals(false, $this->boolean->isValid('false'));
        $this->assertEquals(false, $this->boolean->isValid('true'));
        $this->assertEquals(false, $this->boolean->isValid('0'));
        $this->assertEquals(false, $this->boolean->isValid('1'));
        $this->assertEquals(false, $this->boolean->isValid(0));
        $this->assertEquals(false, $this->boolean->isValid(1));
        $this->assertEquals(false, $this->boolean->isValid(['string', 'string']));
        $this->assertEquals(false, $this->boolean->isValid('string'));
        $this->assertEquals(false, $this->boolean->isValid(1.2));

        $this->boolean = new Boolean(true);

        $this->assertEquals(true, $this->boolean->isValid(true));
        $this->assertEquals(true, $this->boolean->isValid(false));
        $this->assertEquals(true, $this->boolean->isValid('false'));
        $this->assertEquals(true, $this->boolean->isValid('true'));
        $this->assertEquals(true, $this->boolean->isValid('0'));
        $this->assertEquals(true, $this->boolean->isValid('1'));
        $this->assertEquals(true, $this->boolean->isValid(0));
        $this->assertEquals(true, $this->boolean->isValid(1));
        $this->assertEquals(false, $this->boolean->isValid(['string', 'string']));
        $this->assertEquals(false, $this->boolean->isValid('string'));
        $this->assertEquals(false, $this->boolean->isValid(1.2));
        $this->assertEquals($this->boolean->getType(), \Utopia\Validator::TYPE_BOOLEAN);
        $this->assertEquals($this->boolean->isArray(), false);
    }
}
