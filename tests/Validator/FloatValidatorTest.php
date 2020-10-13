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

class FloatValidatorTest extends TestCase
{
    /**
     * @var FloatValidator
     */
    protected $validator = null;

    public function setUp():void
    {
        $this->validator = new FloatValidator();
    }

    public function tearDown():void
    {
        $this->validator = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->validator->isValid(27.25), true);
        $this->assertEquals($this->validator->isValid('abc'), false);
        $this->assertEquals($this->validator->isValid(23), false);
        $this->assertEquals($this->validator->isValid(23.5), true);
        $this->assertEquals($this->validator->isValid(1e7), true);
        $this->assertEquals($this->validator->isValid(true), false);
        $this->assertEquals($this->validator->isValid('23.5'), false);
        $this->assertEquals($this->validator->isValid('23'), false);
    }
}
