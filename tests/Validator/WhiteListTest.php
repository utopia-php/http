<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Tests
 *
 * @link https://github.com/eldadfux/Utopia-PHP-Framework
 * @author Eldad Fux <eldad@fuxie.co.il>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class WhiteListTest extends TestCase
{
    /**
     * @var WhiteList
     */
    protected $whiteList = null;

    public function setUp()
    {
        $this->whiteList = new WhiteList(['string1', 'string2', 3, 4], true);
    }

    public function tearDown()
    {
        $this->whiteList = null;
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->whiteList->isValid('string3'), false);
        $this->assertEquals($this->whiteList->isValid('3'), false);
        $this->assertEquals($this->whiteList->isValid(3), true);
        $this->assertEquals($this->whiteList->isValid(5), false);
        $this->assertEquals($this->whiteList->getList(), ['string1', 'string2', 3, 4]);
    }
}
