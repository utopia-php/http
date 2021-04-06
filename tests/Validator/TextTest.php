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

class TextTest extends TestCase
{
    /**
     * @var Domain
     */
    protected $text = null;

    public function setUp():void
    {
        $this->text = new Text(10);
    }

    public function tearDown():void
    {
        $this->text = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals(true, $this->text->isValid('text'));
        $this->assertEquals(true, $this->text->isValid('7'));
        $this->assertEquals(true, $this->text->isValid('7.9'));
        $this->assertEquals(true, $this->text->isValid('["seven"]'));
        $this->assertEquals(false, $this->text->isValid(["seven"]));
        $this->assertEquals(false, $this->text->isValid(["seven", 8, 9.0]));
        $this->assertEquals(false, $this->text->isValid(false));
        $this->assertEquals(false, $this->text->isArray());
        $this->assertEquals('string', $this->text->getType());
    }
}
