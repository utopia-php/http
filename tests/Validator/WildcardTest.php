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

class WildcardTest extends TestCase
{
    public function testCanValidateWildcard()
    {
        $validator = new Wildcard();
        $this->assertTrue($validator->isValid([0 => 'string', 1 => 'string']));
        $this->assertTrue($validator->isValid(""));
        $this->assertTrue($validator->isValid([]));
        $this->assertTrue($validator->isValid(1));
        $this->assertTrue($validator->isValid(true));
        $this->assertTrue($validator->isValid(false));
        $this->assertFalse($validator->isArray());
        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $validator->getType());
    }
}
