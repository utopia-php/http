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

namespace Utopia\Http\Validator;

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
        $this->assertEquals(false, $this->domain->isValid('subdomain.example_app.com'));
        $this->assertEquals(true, $this->domain->isValid('subdomain-new.example.com'));
        $this->assertEquals(false, $this->domain->isValid('subdomain_new.example.com'));
        $this->assertEquals(true, $this->domain->isValid('localhost'));
        $this->assertEquals(true, $this->domain->isValid('example.io'));
        $this->assertEquals(true, $this->domain->isValid('example.org'));
        $this->assertEquals(true, $this->domain->isValid('example.org'));
        $this->assertEquals(false, $this->domain->isValid(false));
        $this->assertEquals(false, $this->domain->isValid('api.appwrite.io.'));
        $this->assertEquals(false, $this->domain->isValid('.api.appwrite.io'));
        $this->assertEquals(false, $this->domain->isValid('.api.appwrite.io'));
        $this->assertEquals(false, $this->domain->isValid('api..appwrite.io'));
        $this->assertEquals(false, $this->domain->isValid('api-.appwrite.io'));
        $this->assertEquals(false, $this->domain->isValid('api.-appwrite.io'));
        $this->assertEquals(false, $this->domain->isValid('app write.io'));
        $this->assertEquals(false, $this->domain->isValid(' appwrite.io'));
        $this->assertEquals(false, $this->domain->isValid('appwrite.io '));
        $this->assertEquals(false, $this->domain->isValid('-appwrite.io'));
        $this->assertEquals(false, $this->domain->isValid('appwrite.io-'));
        $this->assertEquals(false, $this->domain->isValid('.'));
        $this->assertEquals(false, $this->domain->isValid('..'));
        $this->assertEquals(false, $this->domain->isValid(''));
        $this->assertEquals(false, $this->domain->isValid(['string', 'string']));
        $this->assertEquals(false, $this->domain->isValid(1));
        $this->assertEquals(false, $this->domain->isValid(1.2));
    }

    public function testRestrictions()
    {
        $validator = new Domain([
            Domain::createRestriction('appwrite.network', 3, ['preview-', 'branch-']),
            Domain::createRestriction('fra.appwrite.run', 4),
        ]);

        $this->assertEquals(true, $validator->isValid('google.com'));
        $this->assertEquals(true, $validator->isValid('stage.google.com'));
        $this->assertEquals(true, $validator->isValid('shard4.stage.google.com'));

        $this->assertEquals(false, $validator->isValid('appwrite.network'));
        $this->assertEquals(false, $validator->isValid('preview-a.appwrite.network'));
        $this->assertEquals(false, $validator->isValid('branch-a.appwrite.network'));
        $this->assertEquals(true, $validator->isValid('google.appwrite.network'));
        $this->assertEquals(false, $validator->isValid('stage.google.appwrite.network'));
        $this->assertEquals(false, $validator->isValid('shard4.stage.google.appwrite.network'));

        $this->assertEquals(false, $validator->isValid('fra.appwrite.run'));
        $this->assertEquals(true, $validator->isValid('appwrite.run'));
        $this->assertEquals(true, $validator->isValid('google.fra.appwrite.run'));
        $this->assertEquals(false, $validator->isValid('shard4.google.fra.appwrite.run'));
        $this->assertEquals(true, $validator->isValid('branch-google.fra.appwrite.run'));
        $this->assertEquals(true, $validator->isValid('preview-google.fra.appwrite.run'));
    }
}
