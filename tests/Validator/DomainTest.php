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

class DomainTest extends TestCase
{
    protected Domain $domain;

    public function setUp(): void
    {
        $this->domain = new Domain();
    }

    public function testIsValid()
    {
        // Assertions
        $this->assertEquals(true, $this->domain->isValid('example.com'));
        $this->assertEquals(true, $this->domain->isValid('subdomain.example.com'));
        $this->assertEquals(true, $this->domain->isValid('subdomain.example-app.com'));
        $this->assertEquals(true, $this->domain->isValid('subdomain.example_app.com'));
        $this->assertEquals(true, $this->domain->isValid('subdomain-new.example.com'));
        $this->assertEquals(true, $this->domain->isValid('subdomain_new.example.com'));
        $this->assertEquals(true, $this->domain->isValid('localhost'));
        $this->assertEquals(true, $this->domain->isValid('example.io'));
        $this->assertEquals(true, $this->domain->isValid('example.org'));
        $this->assertEquals(true, $this->domain->isValid('example.org'));
        $this->assertEquals(false, $this->domain->isValid(false));
        $this->assertEquals(false, $this->domain->isValid('.'));
        $this->assertEquals(false, $this->domain->isValid('..'));
        $this->assertEquals(false, $this->domain->isValid(''));
        $this->assertEquals(false, $this->domain->isValid(['string', 'string']));
        $this->assertEquals(false, $this->domain->isValid(1));
        $this->assertEquals(false, $this->domain->isValid(1.2));
    }
}
