<?php

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Http\Adapter\FPM\Request;

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

        $this->assertSame('value1', $this->request->getHeader('custom'));
        $this->assertSame('value2', $this->request->getHeader('custom-new'));

        $headers = $this->request->getHeaders();
        $this->assertIsArray($headers);
        $this->assertCount(2, $headers);
        $this->assertSame('value1', $headers['custom']);
        $this->assertSame('value2', $headers['custom-new']);
    }

    public function testCanAddHeaders()
    {
        $this->request->addHeader('custom', 'value1');
        $this->request->addHeader('custom-new', 'value2');

        $this->assertSame('value1', $this->request->getHeader('custom'));
        $this->assertSame('value2', $this->request->getHeader('custom-new'));
    }

    public function testCanRemoveHeaders()
    {
        $this->request->addHeader('custom', 'value1');
        $this->request->addHeader('custom-new', 'value2');

        $this->assertSame('value1', $this->request->getHeader('custom'));
        $this->assertSame('value2', $this->request->getHeader('custom-new'));

        $this->request->removeHeader('custom');

        $this->assertSame('', $this->request->getHeader('custom'));
        $this->assertSame('value2', $this->request->getHeader('custom-new'));
    }

    public function testCanGetQueryParameter()
    {
        $_GET['key'] = 'value';

        $this->assertSame($this->request->getQuery('key'), 'value');
        $this->assertSame($this->request->getQuery('unknown', 'test'), 'test');
    }

    public function testCanSetQuery()
    {
        $this->request->setQuery(['key' => 'value']);

        $this->assertSame($this->request->getQuery('key'), 'value');
        $this->assertSame($this->request->getQuery('unknown', 'test'), 'test');
    }

    public function testCanGetPayload()
    {
        $this->assertSame($this->request->getPayload('unknown', 'test'), 'test');
    }

    public function testCanSetPayload()
    {
        $this->request->setPayload(['key' => 'value']);

        $this->assertSame($this->request->getPayload('key'), 'value');
        $this->assertSame($this->request->getPayload('unknown', 'test'), 'test');
    }

    public function testCanGetRawPayload()
    {
        $this->assertSame($this->request->getRawPayload(), '');
    }

    public function testCanGetServer()
    {
        $_SERVER['key'] = 'value';

        $this->assertSame($this->request->getServer('key'), 'value');
        $this->assertSame($this->request->getServer('unknown', 'test'), 'test');
    }

    public function testCanSetServer()
    {
        $this->request->setServer('key', 'value');

        $this->assertSame($this->request->getServer('key'), 'value');
        $this->assertSame($this->request->getServer('unknown', 'test'), 'test');
    }

    public function testCanGetCookie()
    {
        $_COOKIE['key'] = 'value';

        $this->assertSame($this->request->getCookie('key'), 'value');
        $this->assertSame($this->request->getCookie('unknown', 'test'), 'test');
    }

    public function testCanGetProtocol()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = null;
        $_SERVER['REQUEST_SCHEME'] = 'http';

        $this->assertSame('http', $this->request->getProtocol());
    }

    public function testCanGetForwardedProtocol()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REQUEST_SCHEME'] = 'http';

        $this->assertSame('https', $this->request->getProtocol());
    }

    public function testCanGetMethod()
    {
        $this->assertSame('UNKNOWN', $this->request->getMethod());

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertSame('GET', $this->request->getMethod());
    }

    public function testCanGetUri()
    {
        $this->assertSame('', $this->request->getURI());

        $_SERVER['REQUEST_URI'] = '/index.html';

        $this->assertSame('/index.html', $this->request->getURI());
    }

    public function testCanSetUri()
    {
        $this->request->setURI('/page.html');

        $this->assertSame('/page.html', $this->request->getURI());
    }

    public function testCanGetQueryString()
    {
        $this->assertSame('', $this->request->getQueryString());

        $_SERVER['QUERY_STRING'] = 'text=hello&value=key';

        $this->assertSame('text=hello&value=key', $this->request->getQueryString());
    }

    public function testCanSetQueryString()
    {
        $this->request->setURI('text=hello&value=key');

        $this->assertSame('text=hello&value=key', $this->request->getURI());
    }

    public function testCanGetPort()
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $this->assertSame('8080', $this->request->getPort());

        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertSame('', $this->request->getPort());
    }

    public function testCanGetHostname()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertSame('localhost', $this->request->getHostname());
    }

    public function testCanGetHostnameWithPort()
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $this->assertSame('localhost', $this->request->getHostname());
    }

    public function testCanGetReferer()
    {
        $this->assertSame('default', $this->request->getReferer('default'));

        $_SERVER['HTTP_REFERER'] = 'referer';

        $this->assertSame('referer', $this->request->getReferer('default'));
    }

    public function testCanGetOrigin()
    {
        $this->assertSame('default', $this->request->getOrigin('default'));

        $_SERVER['HTTP_ORIGIN'] = 'origin';

        $this->assertSame('origin', $this->request->getOrigin('default'));
    }

    public function testCanGetUserAgent()
    {
        $this->assertSame('default', $this->request->getUserAgent('default'));

        $_SERVER['HTTP_USER_AGENT'] = 'user-agent';

        $this->assertSame('user-agent', $this->request->getUserAgent('default'));
    }

    public function testCanGetAccept()
    {
        $this->assertSame('default', $this->request->getAccept('default'));

        $_SERVER['HTTP_ACCEPT'] = 'accept';

        $this->assertSame('accept', $this->request->getAccept('default'));
    }

    public function testCanGetContentRange()
    {
        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499/2000';

        $this->assertSame('bytes', $this->request->getContentRangeUnit());
        $this->assertSame(0, $this->request->getContentRangeStart());
        $this->assertSame(499, $this->request->getContentRangeEnd());
        $this->assertSame(2000, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = ' 0-499/2000';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getContentRangeUnit());
        $this->assertSame(null, $this->request->getContentRangeStart());
        $this->assertSame(null, $this->request->getContentRangeEnd());
        $this->assertSame(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499/';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getContentRangeUnit());
        $this->assertSame(null, $this->request->getContentRangeStart());
        $this->assertSame(null, $this->request->getContentRangeEnd());
        $this->assertSame(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0--499/2000';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getContentRangeUnit());
        $this->assertSame(null, $this->request->getContentRangeStart());
        $this->assertSame(null, $this->request->getContentRangeEnd());
        $this->assertSame(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499test/2000';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getContentRangeUnit());
        $this->assertSame(null, $this->request->getContentRangeStart());
        $this->assertSame(null, $this->request->getContentRangeEnd());
        $this->assertSame(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-49.9/200.0';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getContentRangeUnit());
        $this->assertSame(null, $this->request->getContentRangeStart());
        $this->assertSame(null, $this->request->getContentRangeEnd());
        $this->assertSame(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-49,9/200,0';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getContentRangeUnit());
        $this->assertSame(null, $this->request->getContentRangeStart());
        $this->assertSame(null, $this->request->getContentRangeEnd());
        $this->assertSame(null, $this->request->getContentRangeSize());
    }

    public function testCanGetRange()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=0-499';

        $this->assertSame('bytes', $this->request->getRangeUnit());
        $this->assertSame(0, $this->request->getRangeStart());
        $this->assertSame(499, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = ' 0-499';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getRangeUnit());
        $this->assertSame(null, $this->request->getRangeStart());
        $this->assertSame(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-';
        $this->request = new Request();
        $this->assertSame('bytes', $this->request->getRangeUnit());
        $this->assertSame(0, $this->request->getRangeStart());
        $this->assertSame(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0--499';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getRangeUnit());
        $this->assertSame(null, $this->request->getRangeStart());
        $this->assertSame(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-499test';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getRangeUnit());
        $this->assertSame(null, $this->request->getRangeStart());
        $this->assertSame(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-49.9';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getRangeUnit());
        $this->assertSame(null, $this->request->getRangeStart());
        $this->assertSame(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-49,9';
        $this->request = new Request();
        $this->assertSame(null, $this->request->getRangeUnit());
        $this->assertSame(null, $this->request->getRangeStart());
        $this->assertSame(null, $this->request->getRangeEnd());
    }
}
