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

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Request;

class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    protected $request = null;

    public function setUp():void
    {
        $this->request = new Request();
    }

    public function tearDown():void
    {
        $this->request = null;
    }

    public function testHeader()
    {
        // Mock
        $_SERVER['HTTP_CUSTOM'] = 'value1';
        $_SERVER['HTTP_CUSTOM_NEW'] = 'value2';

        // Assertions
        $this->assertEquals('value1', $this->request->getHeader('custom'));
        $this->assertEquals('value2', $this->request->getHeader('custom-new'));
    }

    public function testGetQuery()
    {
        // Mock
        $_GET['key'] = 'value';

        // Assertions
        $this->assertEquals($this->request->getQuery('key'), 'value');
        $this->assertEquals($this->request->getQuery('unknown', 'test'), 'test');
    }

    public function testGetPayload()
    {
        //Assertions
        $this->assertEquals($this->request->getPayload('unknown', 'test'), 'test');
    }

    public function testGetServer()
    {
        // Mock
        $_SERVER['key'] = 'value';

        // Assertions
        $this->assertEquals($this->request->getServer('key'), 'value');
        $this->assertEquals($this->request->getServer('unknown', 'test'), 'test');
    }

    public function testGetCookie()
    {
        // Mock
        $_COOKIE['key'] = 'value';

        // Assertions
        $this->assertEquals($this->request->getCookie('key'), 'value');
        $this->assertEquals($this->request->getCookie('unknown', 'test'), 'test');
    }

    public function testGetProtocol()
    {
        // Mock
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REQUEST_SCHEME'] = 'http';

        // Assertions
        $this->assertEquals('https', $this->request->getProtocol());

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = null;
        $_SERVER['REQUEST_SCHEME'] = 'http';

        // Assertions
        $this->assertEquals('http', $this->request->getProtocol());
    }

    public function testGetMethod()
    {
        $this->assertEquals('UNKNOWN', $this->request->getMethod());

        // Mock
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Assertions
        $this->assertEquals('GET', $this->request->getMethod());
    }

    public function testGetURI()
    {
        $this->assertEquals('', $this->request->getURI());

        // Mock
        $_SERVER['REQUEST_URI'] = '/index.html';

        // Assertions
        $this->assertEquals('/index.html', $this->request->getURI());
    }

    public function testGetPort()
    {
        // Mock
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        // Assertions
        $this->assertEquals('8080', $this->request->getPort());
        
        // Mock
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_HOST'] = 'localhost';

        // Assertions
        $this->assertEquals('', $this->request->getPort());
    }

    public function testGetHostname()
    {
        // Mock
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        // Assertions
        $this->assertEquals('localhost', $this->request->getHostname());
        
        // Mock
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_HOST'] = 'localhost';

        // Assertions
        $this->assertEquals('localhost', $this->request->getHostname());
    }

    public function testGetReferer()
    {
        $this->assertEquals('default', $this->request->getReferer('default'));

        // Mock
        $_SERVER['HTTP_REFERER'] = 'referer';

        // Assertions
        $this->assertEquals('referer', $this->request->getReferer('default'));
    }

    public function testGetOrigin()
    {
        $this->assertEquals('default', $this->request->getOrigin('default'));

        // Mock
        $_SERVER['HTTP_ORIGIN'] = 'origin';

        // Assertions
        $this->assertEquals('origin', $this->request->getOrigin('default'));
    }

    public function testGetUserAgent()
    {
        $this->assertEquals('default', $this->request->getUserAgent('default'));

        // Mock
        $_SERVER['HTTP_USER_AGENT'] = 'user-agent';

        // Assertions
        $this->assertEquals('user-agent', $this->request->getUserAgent('default'));
    }

    public function testGetAccept()
    {
        $this->assertEquals('default', $this->request->getAccept('default'));

        // Mock
        $_SERVER['HTTP_ACCEPT'] = 'accept';

        // Assertions
        $this->assertEquals('accept', $this->request->getAccept('default'));
    }

    public function testContentRange()
    {
        $_SERVER['HTTP_CONTENT_RANGE'] = "bytes 0-499/2000";
        
        $this->assertEquals('bytes', $this->request->getContentRangeUnit());
        $this->assertEquals(0, $this->request->getContentRangeStart());
        $this->assertEquals(499, $this->request->getContentRangeEnd());
        $this->assertEquals(2000, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = " 0-499/2000";
        
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = "bytes 0-499/";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = "bytes 0--499/2000";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = "bytes 0-499test/2000";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = "bytes 0-49.9/200.0";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());

        $_SERVER['HTTP_CONTENT_RANGE'] = "bytes 0-49,9/200,0";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getContentRangeUnit());
        $this->assertEquals(null, $this->request->getContentRangeStart());
        $this->assertEquals(null, $this->request->getContentRangeEnd());
        $this->assertEquals(null, $this->request->getContentRangeSize());
    }

    public function testRange()
    {
        $_SERVER['HTTP_RANGE'] = "bytes=0-499";
        
        $this->assertEquals('bytes', $this->request->getRangeUnit());
        $this->assertEquals(0, $this->request->getRangeStart());
        $this->assertEquals(499, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = " 0-499";
        
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = "bytes=0-";
        $this->request = new Request();
        $this->assertEquals('bytes', $this->request->getRangeUnit());
        $this->assertEquals(0, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = "bytes=0--499";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = "bytes=0-499test";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = "bytes=0-49.9";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());

        $_SERVER['HTTP_RANGE'] = "bytes=0-49,9";
        $this->request = new Request();
        $this->assertEquals(null, $this->request->getRangeUnit());
        $this->assertEquals(null, $this->request->getRangeStart());
        $this->assertEquals(null, $this->request->getRangeEnd());
    }
}
