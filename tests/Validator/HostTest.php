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

class HostTest extends TestCase
{
    protected Host $host;

    public function setUp(): void
    {
        $this->host = new Host(['example.io', 'subdomain.example.test', 'localhost']);
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals($this->host->isValid('https://example.io/link'), true);
        $this->assertEquals($this->host->isValid('https://localhost'), true);
        $this->assertEquals($this->host->isValid('localhost'), false);
        $this->assertEquals($this->host->isValid('http://subdomain.example.test/path'), true);
        $this->assertEquals($this->host->isValid('http://test.subdomain.example.test/path'), false);
        $this->assertEquals($this->host->getType(), 'string');
    }
}
