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

class IntegerTest extends TestCase
{
    /**
     * @var \Utopia\HTTP\Validator\Integer
     */
    protected $validator = null;
    
    /**
     * @var \Utopia\HTTP\Validator\Integer
     */
    protected $looseValidator = null;

    public function setUp():void
    {
        $this->validator = new Integer();
        $this->looseValidator = new Integer(true);
    }

    public function tearDown():void
    {
        $this->validator = null;
        $this->looseValidator = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->validator->isValid(23), true);
        $this->assertEquals($this->validator->isValid('23'), false);
        $this->assertEquals($this->validator->isValid(23.5), false);
        $this->assertEquals($this->validator->isValid('23.5'), false);
        $this->assertEquals($this->validator->isValid(null), false);
        $this->assertEquals($this->validator->isValid(true), false);
        $this->assertEquals($this->validator->isValid(false), false);
        $this->assertEquals($this->validator->getType(), \Utopia\HTTP\Validator::TYPE_INTEGER);
        $this->assertEquals($this->validator->isArray(), false);

        // Assertions loose
        $this->assertEquals($this->looseValidator->isValid(23), true);
        $this->assertEquals($this->looseValidator->isValid('23'), true);
        $this->assertEquals($this->looseValidator->isValid(23.5), false);
        $this->assertEquals($this->looseValidator->isValid('23.5'), false);
        $this->assertEquals($this->looseValidator->isValid(null), false);
        $this->assertEquals($this->looseValidator->isValid(true), false);
        $this->assertEquals($this->looseValidator->isValid(false), false);
        $this->assertEquals($this->looseValidator->getType(), \Utopia\HTTP\Validator::TYPE_INTEGER);
        $this->assertEquals($this->looseValidator->isArray(), false);
    }
}
