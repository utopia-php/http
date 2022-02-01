<?php
/**
 * Utopia HTTP
 *
 * @package HTTP
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/http
 * @author Appwrite Team <team@appwrite.io>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\HTTP\Validator;

use PHPUnit\Framework\TestCase;

class FloatValidatorTest extends TestCase
{
    /**
     * @var FloatValidator
     */
    protected $validator = null;

    /**
     * @var FloatValidator
     */
    protected $looseValidator = null;

    public function setUp():void
    {
        $this->validator = new FloatValidator();
        $this->looseValidator = new FloatValidator(true);
    }

    public function tearDown():void
    {
        $this->validator = null;
        $this->looseValidator = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->validator->isValid(27.25), true);
        $this->assertEquals($this->validator->isValid('abc'), false);
        $this->assertEquals($this->validator->isValid(23), true);
        $this->assertEquals($this->validator->isValid(23.5), true);
        $this->assertEquals($this->validator->isValid(1e7), true);
        $this->assertEquals($this->validator->isValid(true), false);
        $this->assertEquals($this->validator->isValid('23.5'), false);
        $this->assertEquals($this->validator->isValid('23'), false);
        $this->assertEquals($this->validator->getType(), \Utopia\Validator::TYPE_FLOAT);
        $this->assertEquals($this->validator->isArray(), false);

        // Assertions Loose
        $this->assertEquals($this->looseValidator->isValid(27.25), true);
        $this->assertEquals($this->looseValidator->isValid('abc'), false);
        $this->assertEquals($this->looseValidator->isValid(23), true);
        $this->assertEquals($this->looseValidator->isValid(23.5), true);
        $this->assertEquals($this->looseValidator->isValid(1e7), true);
        $this->assertEquals($this->looseValidator->isValid(true), false);
        $this->assertEquals($this->looseValidator->isValid('23.5'), true);
        $this->assertEquals($this->looseValidator->isValid('23'), true);
        $this->assertEquals($this->looseValidator->getType(), \Utopia\Validator::TYPE_FLOAT);
        $this->assertEquals($this->looseValidator->isArray(), false);
    }
}
