<?php

declare(strict_types=1);

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Http\Adapter\FPM\Request;

final class RequestTest extends TestCase
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

    public function testCanGetHeaders(): void
    {
        $_SERVER['HTTP_CUSTOM'] = 'value1';
        $_SERVER['HTTP_CUSTOM_NEW'] = 'value2';

        $this->assertSame('value1', $this->request->getHeader('custom'));
        $this->assertSame('value2', $this->request->getHeader('custom-new'));

        $headers = $this->request->getHeaders();
        $this->assertCount(2, $headers);
        $this->assertSame('value1', $headers['custom']);
        $this->assertSame('value2', $headers['custom-new']);
    }

    public function testCanAddHeaders(): void
    {
        $this->request->addHeader('custom', 'value1');
        $this->request->addHeader('custom-new', 'value2');

        $this->assertSame('value1', $this->request->getHeader('custom'));
        $this->assertSame('value2', $this->request->getHeader('custom-new'));
    }

    public function testCanRemoveHeaders(): void
    {
        $this->request->addHeader('custom', 'value1');
        $this->request->addHeader('custom-new', 'value2');

        $this->assertSame('value1', $this->request->getHeader('custom'));
        $this->assertSame('value2', $this->request->getHeader('custom-new'));

        $this->request->removeHeader('custom');

        $this->assertSame('', $this->request->getHeader('custom'));
        $this->assertSame('value2', $this->request->getHeader('custom-new'));
    }

    public function testCanGetQueryParameter(): void
    {
        $_GET['key'] = 'value';

        $this->assertSame('value', $this->request->getQuery('key'));
        $this->assertSame('test', $this->request->getQuery('unknown', 'test'));
    }

    public function testCanSetQueryString(): void
    {
        $this->request->setQueryString(['key' => 'value']);

        $this->assertSame('value', $this->request->getQuery('key'));
        $this->assertSame('test', $this->request->getQuery('unknown', 'test'));
    }

    public function testCanGetPayload(): void
    {
        $this->assertSame('test', $this->request->getPayload('unknown', 'test'));
    }

    public function testCanSetPayload(): void
    {
        $this->request->setPayload(['key' => 'value']);

        $this->assertSame('value', $this->request->getPayload('key'));
        $this->assertSame('test', $this->request->getPayload('unknown', 'test'));
    }

    public function testCanGetRawPayload(): void
    {
        $this->assertSame('', $this->request->getRawPayload());
    }

    public function testCanGetServer(): void
    {
        $_SERVER['key'] = 'value';

        $this->assertSame('value', $this->request->getServer('key'));
        $this->assertSame('test', $this->request->getServer('unknown', 'test'));
    }

    public function testCanSetServer(): void
    {
        $this->request->setServer('key', 'value');

        $this->assertSame('value', $this->request->getServer('key'));
        $this->assertSame('test', $this->request->getServer('unknown', 'test'));
    }

    public function testCanGetCookie(): void
    {
        $_COOKIE['key'] = 'value';

        $this->assertSame('value', $this->request->getCookie('key'));
        $this->assertSame('test', $this->request->getCookie('unknown', 'test'));
    }

    public function testCanGetProtocol(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = null;
        $_SERVER['REQUEST_SCHEME'] = 'http';

        $this->assertSame('http', $this->request->getProtocol());
    }

    public function testCanGetForwardedProtocol(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REQUEST_SCHEME'] = 'http';

        $this->assertSame('https', $this->request->getProtocol());
    }

    public function testCanGetMethod(): void
    {
        $this->assertSame('UNKNOWN', $this->request->getMethod());

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertSame('GET', $this->request->getMethod());
    }

    public function testCanGetUri(): void
    {
        $this->assertSame('', $this->request->getURI());

        $_SERVER['REQUEST_URI'] = '/index.html';

        $this->assertSame('/index.html', $this->request->getURI());
    }

    public function testCanSetUri(): void
    {
        $this->request->setURI('/page.html');

        $this->assertSame('/page.html', $this->request->getURI());
    }

    public function testCanGetPort(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $this->assertSame('8080', $this->request->getPort());

        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertSame('', $this->request->getPort());
    }

    public function testCanGetHostname(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertSame('localhost', $this->request->getHostname());
    }

    public function testCanGetHostnameWithPort(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $this->assertSame('localhost', $this->request->getHostname());
    }

    public function testCanGetReferer(): void
    {
        $this->assertSame('default', $this->request->getReferer('default'));

        $_SERVER['HTTP_REFERER'] = 'referer';

        $this->assertSame('referer', $this->request->getReferer('default'));
    }

    public function testCanGetOrigin(): void
    {
        $this->assertSame('default', $this->request->getOrigin('default'));

        $_SERVER['HTTP_ORIGIN'] = 'origin';

        $this->assertSame('origin', $this->request->getOrigin('default'));
    }

    public function testCanGetUserAgent(): void
    {
        $this->assertSame('default', $this->request->getUserAgent('default'));

        $_SERVER['HTTP_USER_AGENT'] = 'user-agent';

        $this->assertSame('user-agent', $this->request->getUserAgent('default'));
    }

    public function testCanGetAccept(): void
    {
        $this->assertSame('default', $this->request->getAccept('default'));

        $_SERVER['HTTP_ACCEPT'] = 'accept';

        $this->assertSame('accept', $this->request->getAccept('default'));
    }

    public function testCanGetContentRange(): void
    {
        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499/2000';

        $this->assertSame('bytes', $this->request->getContentRangeUnit());
        $this->assertSame(0, $this->request->getContentRangeStart());
        $this->assertSame(499, $this->request->getContentRangeEnd());
        $this->assertSame(2000, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = ' 0-499/2000';
        $this->request = new Request();
        $this->assertNull($this->request->getContentRangeUnit());
        $this->assertNull($this->request->getContentRangeStart());
        $this->assertNull($this->request->getContentRangeEnd());
        $this->assertNull($this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499/';
        $this->request = new Request();
        $this->assertNull($this->request->getContentRangeUnit());
        $this->assertNull($this->request->getContentRangeStart());
        $this->assertNull($this->request->getContentRangeEnd());
        $this->assertNull($this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0--499/2000';
        $this->request = new Request();
        $this->assertNull($this->request->getContentRangeUnit());
        $this->assertNull($this->request->getContentRangeStart());
        $this->assertNull($this->request->getContentRangeEnd());
        $this->assertNull($this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-499test/2000';
        $this->request = new Request();
        $this->assertNull($this->request->getContentRangeUnit());
        $this->assertNull($this->request->getContentRangeStart());
        $this->assertNull($this->request->getContentRangeEnd());
        $this->assertNull($this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-49.9/200.0';
        $this->request = new Request();
        $this->assertNull($this->request->getContentRangeUnit());
        $this->assertNull($this->request->getContentRangeStart());
        $this->assertNull($this->request->getContentRangeEnd());
        $this->assertNull($this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-49,9/200,0';
        $this->request = new Request();
        $this->assertNull($this->request->getContentRangeUnit());
        $this->assertNull($this->request->getContentRangeStart());
        $this->assertNull($this->request->getContentRangeEnd());
        $this->assertNull($this->request->getContentRangeSize());
    }

    public function testCanGetRange(): void
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=0-499';

        $this->assertSame('bytes', $this->request->getRangeUnit());
        $this->assertSame(0, $this->request->getRangeStart());
        $this->assertSame(499, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = ' 0-499';
        $this->request = new Request();
        $this->assertNull($this->request->getRangeUnit());
        $this->assertNull($this->request->getRangeStart());
        $this->assertNull($this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-';
        $this->request = new Request();
        $this->assertSame('bytes', $this->request->getRangeUnit());
        $this->assertSame(0, $this->request->getRangeStart());
        $this->assertNull($this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0--499';
        $this->request = new Request();
        $this->assertNull($this->request->getRangeUnit());
        $this->assertNull($this->request->getRangeStart());
        $this->assertNull($this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-499test';
        $this->request = new Request();
        $this->assertNull($this->request->getRangeUnit());
        $this->assertNull($this->request->getRangeStart());
        $this->assertNull($this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-49.9';
        $this->request = new Request();
        $this->assertNull($this->request->getRangeUnit());
        $this->assertNull($this->request->getRangeStart());
        $this->assertNull($this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-49,9';
        $this->request = new Request();
        $this->assertNull($this->request->getRangeUnit());
        $this->assertNull($this->request->getRangeStart());
        $this->assertNull($this->request->getRangeEnd());
    }

    public function testCanGetSizeWithArrayHeaders(): void
    {
        $this->request->addHeader('content-type', 'application/json');

        $reflection = new \ReflectionClass($this->request);
        $headersProperty = $reflection->getProperty('headers');

        $headers = $headersProperty->getValue($this->request) ?? [];
        $headers['accept'] = ['application/json', 'text/html'];
        $headers['x-custom'] = ['value1', 'value2', 'value3'];
        $headersProperty->setValue($this->request, $headers);

        $size = $this->request->getSize();

        $this->assertGreaterThan(0, $size);
    }
}
