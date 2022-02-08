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

class CharTest extends TestCase
{
    public function setUp():void
    {
    }

    public function tearDown():void
    {
    }

    public function testIsValid()
    {
        // Test lowercase alphabet
        $this->validator = new Char(100, Char::ALPHABET_LOWER);
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $this->validator->getType());
        $this->assertEquals(false, $this->validator->isArray());
        $this->assertEquals(true, $this->validator->isValid('qwertzuiopasdfghjklyxcvbnm'));
        $this->assertEquals(true, $this->validator->isValid('hello'));
        $this->assertEquals(true, $this->validator->isValid('world'));
        $this->assertEquals(false, $this->validator->isValid('hello world'));
        $this->assertEquals(false, $this->validator->isValid('Hello'));
        $this->assertEquals(false, $this->validator->isValid('worlD'));
        $this->assertEquals(false, $this->validator->isValid('hello123'));

        // Test uppercase alphabet
        $this->validator = new Char(100, Char::ALPHABET_UPPER);
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $this->validator->getType());
        $this->assertEquals(false, $this->validator->isArray());
        $this->assertEquals(true, $this->validator->isValid('QWERTZUIOPASDFGHJKLYXCVBNM'));
        $this->assertEquals(true, $this->validator->isValid('HELLO'));
        $this->assertEquals(true, $this->validator->isValid('WORLD'));
        $this->assertEquals(false, $this->validator->isValid('HELLO WORLD'));
        $this->assertEquals(false, $this->validator->isValid('hELLO'));
        $this->assertEquals(false, $this->validator->isValid('WORLd'));
        $this->assertEquals(false, $this->validator->isValid('HELLO123'));

        // Test numbers
        $this->validator = new Char(100, Char::NUMBERS);
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $this->validator->getType());
        $this->assertEquals(false, $this->validator->isArray());
        $this->assertEquals(true, $this->validator->isValid('1234567890'));
        $this->assertEquals(true, $this->validator->isValid('123'));
        $this->assertEquals(false, $this->validator->isValid('123 456'));
        $this->assertEquals(false, $this->validator->isValid('hello123'));

        // Test combination of whitelists
        $this->validator = new Char(100, [
            ...Char::ALPHABET_LOWER,
            ...Char::ALPHABET_UPPER,
            ...Char::NUMBERS
        ]);
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $this->validator->getType());
        $this->assertEquals(false, $this->validator->isArray());
        $this->assertEquals(true, $this->validator->isValid('1234567890'));
        $this->assertEquals(true, $this->validator->isValid('qwertzuiopasdfghjklyxcvbnm'));
        $this->assertEquals(true, $this->validator->isValid('QWERTZUIOPASDFGHJKLYXCVBNM'));
        $this->assertEquals(true, $this->validator->isValid('QWERTZUIOPASDFGHJKLYXCVBNMqwertzuiopasdfghjklyxcvbnm1234567890'));
        $this->assertEquals(false, $this->validator->isValid('hello-world'));
        $this->assertEquals(false, $this->validator->isValid('hello_world'));
        $this->assertEquals(false, $this->validator->isValid('hello/world'));

        // Test length validation
        $this->validator = new Char(5, Char::ALPHABET_LOWER);
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $this->validator->getType());
        $this->assertEquals(false, $this->validator->isArray());
        $this->assertEquals(true, $this->validator->isValid('hell'));
        $this->assertEquals(true, $this->validator->isValid('hello'));
        $this->assertEquals(false, $this->validator->isValid('hellow'));
    }
}
