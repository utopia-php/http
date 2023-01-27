<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Request;

class RequestTest extends TestCase
{
    protected ?Request $request;

    public function setUp(): void
    {
        $this->request = new Request();
    }

    public function tearDown(): void
    {
        $this->request = null;
    }

    public function testCanGetHeaders()
    {
        $_SERVER['HTTP_CUSTOM'] = 'value1';
        $_SERVER['HTTP_CUSTOM_NEW'] = 'value2';

        $this->assertEquals('value1', $this->request->getHeader('custom'));
        $this->assertEquals('value2', $this->request->getHeader('custom-new'));

        $headers = $this->request->getHeaders();
        $this->assertIsArray($headers);
        $this->assertCount(2, $headers);
        $this->assertEquals('value1', $headers['custom']);
        $this->assertEquals('value2', $headers['custom-new']);
    }

    public function testCanAddHeaders()
    {
        $this->request->addHeader('custom', 'value1');
        $this->request->addHeader('custom-new', 'value2');

        $this->assertEquals('value1', $this->request->getHeader('custom'));
        $this->assertEquals('value2', $this->request->getHeader('custom-new'));
    }

    public function testCanRemoveHeaders()
    {
        $this->request->addHeader('custom', 'value1');
        $this->request->addHeader('custom-new', 'value2');

        $this->assertEquals('value1', $this->request->getHeader('custom'));
        $this->assertEquals('value2', $this->request->getHeader('custom-new'));

        $this->request->removeHeader('custom');

        $this->assertEquals(null, $this->request->getHeader('custom'));
        $this->assertEquals('value2', $this->request->getHeader('custom-new'));
    }

    public function testCanGetQueryParameter()
    {
        $_GET['key'] = 'value';

        $this->assertEquals($this->request->getQuery('key'), 'value');
        $this->assertEquals($this->request->getQuery('unknown', 'test'), 'test');
    }

    public function testCanSetQueryString()
    {
        $this->request->setQueryString(['key' => 'value']);

        $this->assertEquals($this->request->getQuery('key'), 'value');
        $this->assertEquals($this->request->getQuery('unknown', 'test'), 'test');
    }

    public function testCanGetPayload()
    {
        $this->assertEquals($this->request->getPayload('unknown', 'test'), 'test');
    }

    public function testCanSetPayload()
    {
        $this->request->setPayload(['key' => 'value']);

        $this->assertEquals($this->request->getPayload('key'), 'value');
        $this->assertEquals($this->request->getPayload('unknown', 'test'), 'test');
    }

    public function testCanGetRawPayload()
    {
        $this->assertEquals($this->request->getRawPayload(), '');
    }

    public function testCanGetServer()
    {
        $_SERVER['key'] = 'value';

        $this->assertEquals($this->request->getServer('key'), 'value');
        $this->assertEquals($this->request->getServer('unknown', 'test'), 'test');
    }

    public function testCanSetServer()
    {
        $this->request->setServer('key', 'value');

        $this->assertEquals($this->request->getServer('key'), 'value');
        $this->assertEquals($this->request->getServer('unknown', 'test'), 'test');
    }

    public function testCanGetCookie()
    {
        $_COOKIE['key'] = 'value';

        $this->assertEquals($this->request->getCookie('key'), 'value');
        $this->assertEquals($this->request->getCookie('unknown', 'test'), 'test');
    }

    public function testCanGetProtocol()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = null;
        $_SERVER['REQUEST_SCHEME'] = 'http';

        $this->assertEquals('http', $this->request->getProtocol());
    }

    public function testCanGetForwardedProtocol()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REQUEST_SCHEME'] = 'http';

        $this->assertEquals('https', $this->request->getProtocol());
    }

    public function testCanGetMethod()
    {
        $this->assertEquals('UNKNOWN', $this->request->getMethod());

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertEquals('GET', $this->request->getMethod());
    }

    public function testCanGetUri()
    {
        $this->assertEquals('', $this->request->getURI());

        $_SERVER['REQUEST_URI'] = '/index.html';

        $this->assertEquals('/index.html', $this->request->getURI());
    }

    public function testCanSetUri()
    {
        $this->request->setURI('/page.html');

        $this->assertEquals('/page.html', $this->request->getURI());
    }

    public function testCanGetPort()
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $this->assertEquals('8080', $this->request->getPort());

        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertEquals('', $this->request->getPort());
    }

    public function testCanGetHostname()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertEquals('localhost', $this->request->getHostname());
    }

    public function testCanGetHostnameWithPort()
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $this->assertEquals('localhost', $this->request->getHostname());
    }

    public function testCanGetReferer()
    {
        $this->assertEquals('default', $this->request->getReferer('default'));

        $_SERVER['HTTP_REFERER'] = 'referer';

        $this->assertEquals('referer', $this->request->getReferer('default'));
    }

    public function testCanGetOrigin()
    {
        $this->assertEquals('default', $this->request->getOrigin('default'));

        $_SERVER['HTTP_ORIGIN'] = 'origin';

        $this->assertEquals('origin', $this->request->getOrigin('default'));
    }

    public function testCanGetUserAgent()
    {
        $this->assertEquals('default', $this->request->getUserAgent('default'));

        $_SERVER['HTTP_USER_AGENT'] = 'user-agent';

        $this->assertEquals('user-agent', $this->request->getUserAgent('default'));
    }

    public function testCanGetAccept()
    {
        $this->assertEquals('default', $this->request->getAccept('default'));

        $_SERVER['HTTP_ACCEPT'] = 'accept';

        $this->assertEquals('accept', $this->request->getAccept('default'));
    }

    public function testCanGetContentRange()
    {
        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499/2000';

        $this->assertEquals('bytes', $this->request->getContentRangeUnit());
        $this->assertEquals(0, $this->request->getContentRangeStart());
        $this->assertEquals(499, $this->request->getContentRangeEnd());
        $this->assertEquals(2000, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = ' 0-499/2000';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499/';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0--499/2000';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499test/2000';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-49.9/200.0';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-49,9/200,0';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());
    }

    public function testCanGetRange()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=0-499';

        $this->assertEquals('bytes', $this->request->getRangeUnit());
        $this->assertEquals(0, $this->request->getRangeStart());
        $this->assertEquals(499, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = ' 0-499';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-';
        $this->request = new Request();
        $this->assertEquals('bytes', $this->request->getRangeUnit());
        $this->assertEquals(0, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0--499';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-499test';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-49.9';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-49,9';
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());
    }
}
