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

class JSONTest extends TestCase
{
    /**
     * @var JSON
     */
    protected $json = null;

    public function setUp():void
    {
        $this->json = new JSON();
    }

    public function tearDown():void
    {
        $this->json = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals(false, $this->json->isValid(''));
        $this->assertEquals(false, $this->json->isValid(false));
        $this->assertEquals(false, $this->json->isValid(null));
        $this->assertEquals(false, $this->json->isValid('string'));
        $this->assertEquals(false, $this->json->isValid(1));
        $this->assertEquals(false, $this->json->isValid(1.2));
        $this->assertEquals(false, $this->json->isValid("{'test': 'demo'}"));
        $this->assertEquals(true, $this->json->isValid('{}'));
        $this->assertEquals(true, $this->json->isValid([]));
        $this->assertEquals(true, $this->json->isValid(['test']));
        $this->assertEquals(true, $this->json->isValid(['test' => 'demo']));
        $this->assertEquals(true, $this->json->isValid('{"test": "demo"}'));
        $this->assertEquals($this->json->getType(), \Utopia\Validator::TYPE_OBJECT);
        $this->assertEquals($this->json->isArray(), false);
    }
}
