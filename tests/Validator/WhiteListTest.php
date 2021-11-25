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

class WhiteListTest extends TestCase
{
    public function testIsValid()
    {
        $whiteList = new WhiteList(['string1', 'string2', 3, 4], true);

        // Assertions
        $this->assertEquals($whiteList->isValid('string3'), false);
        $this->assertEquals($whiteList->isValid('STRING1'), false);
        $this->assertEquals($whiteList->isValid('strIng1'), false);
        $this->assertEquals($whiteList->isValid('3'), false);
        $this->assertEquals($whiteList->isValid(3), true);
        $this->assertEquals($whiteList->isValid(5), false);
        $this->assertEquals($whiteList->getList(), ['string1', 'string2', 3, 4]);
        $this->assertEquals($whiteList->getType(), \Utopia\Validator::TYPE_STRING); //string by default
        $this->assertEquals($whiteList->isArray(), false);
        
        $whiteList = new WhiteList(['string1', 'string2', 3, 4], false);

        // Assertions
        $this->assertEquals($whiteList->isValid('string3'), false);
        $this->assertEquals($whiteList->isValid('STRING1'), true);
        $this->assertEquals($whiteList->isValid('strIng1'), true);
        $this->assertEquals($whiteList->isValid('3'), true);
        $this->assertEquals($whiteList->isValid(3), true);
        $this->assertEquals($whiteList->isValid(5), false);
        $this->assertEquals($whiteList->getList(), ['string1', 'string2', 3, 4]);
        
        $whiteList = new WhiteList(['STRING1', 'STRING2', 3, 4], false);

        // Assertions
        $this->assertEquals($whiteList->isValid('string3'), false);
        $this->assertEquals($whiteList->isValid('STRING1'), true);
        $this->assertEquals($whiteList->isValid('strIng1'), true);
        $this->assertEquals($whiteList->isValid('3'), true);
        $this->assertEquals($whiteList->isValid(3), true);
        $this->assertEquals($whiteList->isValid(5), false);
        $this->assertEquals($whiteList->getList(), ['string1', 'string2', 3, 4]);
    }
}
