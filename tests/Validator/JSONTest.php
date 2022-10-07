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

class JSONTest extends TestCase
{
    public function testCanValidateJson(): void
    {
        $json = new JSON();
        $this->assertTrue($json->isValid('{}'));
        $this->assertTrue($json->isValid([]));
        $this->assertTrue($json->isValid(['test']));
        $this->assertTrue($json->isValid(['test' => 'demo']));
        $this->assertTrue($json->isValid('{"test": "demo"}'));

        $this->assertFalse($json->isValid(''));
        $this->assertFalse($json->isValid(false));
        $this->assertFalse($json->isValid(null));
        $this->assertFalse($json->isValid('string'));
        $this->assertFalse($json->isValid(1));
        $this->assertFalse($json->isValid(1.2));
        $this->assertFalse($json->isValid("{'test': 'demo'}"));
        $this->assertFalse($json->isArray());
        $this->assertEquals(\Utopia\Validator::TYPE_OBJECT, $json->getType());
    }
}
