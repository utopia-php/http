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

class HostnameTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function testIsValid()
    {
        // Basic tests
        $validator = new Hostname();

        $this->assertEquals(\Utopia\Validator::TYPE_STRING, $validator->getType());
        $this->assertEquals(false, $validator->isArray());

        $this->assertEquals(true, $validator->isValid('myweb.com'));
        $this->assertEquals(true, $validator->isValid('httpmyweb.com'));
        $this->assertEquals(true, $validator->isValid('httpsmyweb.com'));
        $this->assertEquals(true, $validator->isValid('wsmyweb.com'));
        $this->assertEquals(true, $validator->isValid('wssmyweb.com'));
        $this->assertEquals(true, $validator->isValid('vercel.app'));
        $this->assertEquals(true, $validator->isValid('web.vercel.app'));
        $this->assertEquals(true, $validator->isValid('my-web.vercel.app'));
        $this->assertEquals(true, $validator->isValid('my-project.my-web.vercel.app'));
        $this->assertEquals(true, $validator->isValid('my-commit.my-project.my-web.vercel.app'));
        $this->assertEquals(true, $validator->isValid('myapp.co.uk'));
        $this->assertEquals(true, $validator->isValid('*.myapp.com'));
        $this->assertEquals(true, $validator->isValid('myapp.*'));
        $this->assertEquals(true, $validator->isValid('*'));

        $this->assertEquals(false, $validator->isValid('https://myweb.com'));
        $this->assertEquals(false, $validator->isValid('ws://myweb.com'));
        $this->assertEquals(false, $validator->isValid('wss://myweb.com'));
        $this->assertEquals(false, $validator->isValid('http://myweb.com'));
        $this->assertEquals(false, $validator->isValid('http://myweb.com:3000'));
        $this->assertEquals(false, $validator->isValid('http://myweb.com/blog'));
        $this->assertEquals(false, $validator->isValid('myweb.com:80'));
        $this->assertEquals(false, $validator->isValid('myweb.com:3000'));
        $this->assertEquals(false, $validator->isValid('myweb.com/blog'));
        $this->assertEquals(false, $validator->isValid('myweb.com/blog/article1'));

        // allowList tests
        $validator = new Hostname([
            'myweb.vercel.app',
            'myweb.com',
            '*.myapp.com',
            '*.*.myrepo.com'
        ]);

        $this->assertEquals(true, $validator->isValid('myweb.vercel.app'));
        $this->assertEquals(false, $validator->isValid('myweb.vercel.com'));
        $this->assertEquals(false, $validator->isValid('myweb2.vercel.app'));
        $this->assertEquals(false, $validator->isValid('vercel.app'));
        $this->assertEquals(false, $validator->isValid('mycommit.myweb.vercel.app'));

        $this->assertEquals(true, $validator->isValid('myweb.com'));
        $this->assertEquals(false, $validator->isValid('myweb.eu'));
        $this->assertEquals(false, $validator->isValid('project.myweb.eu'));
        $this->assertEquals(false, $validator->isValid('commit.project.myweb.eu'));

        $this->assertEquals(true, $validator->isValid('project1.myapp.com'));
        $this->assertEquals(true, $validator->isValid('project2.myapp.com'));
        $this->assertEquals(true, $validator->isValid('project-with-dash.myapp.com'));
        $this->assertEquals(true, $validator->isValid('anything.myapp.com'));
        $this->assertEquals(false, $validator->isValid('anything.myapp.eu'));
        $this->assertEquals(false, $validator->isValid('commit.anything.myapp.com'));

        $this->assertEquals(true, $validator->isValid('commit1.project1.myrepo.com'));
        $this->assertEquals(true, $validator->isValid('commit2.project3.myrepo.com'));
        $this->assertEquals(true, $validator->isValid('commit-with-dash.project-with-dash.myrepo.com'));
        $this->assertEquals(false, $validator->isValid('myrepo.com'));
        $this->assertEquals(false, $validator->isValid('project1.myrepo.com'));
        $this->assertEquals(false, $validator->isValid('line1.commit1.project1.myrepo.com'));

        $validator = new Hostname(['localhost']);
        $this->assertEquals(true, $validator->isValid('localhost'));

        // edge-cases tests
        $validator = new Hostname(['netlify.*']);
        $this->assertEquals(true, $validator->isValid('netlify.com'));
        $this->assertEquals(true, $validator->isValid('netlify.eu'));
        $this->assertEquals(true, $validator->isValid('netlify.app'));

        $validator = new Hostname(['*']);
        $this->assertEquals(true, $validator->isValid('localhost'));
        $this->assertEquals(true, $validator->isValid('anything')); // Like localhost
        $this->assertEquals(true, $validator->isValid('anything.com'));
        $this->assertEquals(true, $validator->isValid('anything.with.subdomains.eu'));
    }
}
