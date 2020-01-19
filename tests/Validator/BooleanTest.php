<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
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
    protected $boolean = null;

    public function setUp()
    {
        $this->boolean = new Boolean();
    }

    public function tearDown()
    {
        $this->boolean = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals(true, $this->boolean->isValid(true));
        $this->assertEquals(true, $this->boolean->isValid(false));
        $this->assertEquals(false, $this->boolean->isValid(['string', 'string']));
        $this->assertEquals(false, $this->boolean->isValid('string'));
        $this->assertEquals(false, $this->boolean->isValid(1));
        $this->assertEquals(false, $this->boolean->isValid(1.2));
    }
}
