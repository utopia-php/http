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

class IntegerTest extends TestCase
{
    /**
     * @var \Utopia\Validator\Integer
     */
    protected $validator = null;

    public function setUp():void
    {
        $this->validator = new Integer();
    }

    public function tearDown():void
    {
        $this->validator = null;
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
    }
}
