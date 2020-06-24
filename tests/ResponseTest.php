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

namespace Utopia;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /**
     * @var Response
     */
    protected $response = null;

    public function setUp()
    {
        $this->response = new Response();
    }

    public function tearDown()
    {
        $this->response = null;
    }

    public function testSetContentType()
    {
        $contentType = $this->response->setContentType(Response::CONTENT_TYPE_HTML);

        // Assertions
        $this->assertInstanceOf('Utopia\Response', $contentType);
    }

    public function testSetStatus()
    {
        $status = $this->response->setStatusCode(Response::STATUS_CODE_OK);

        // Assertions
        $this->assertInstanceOf('Utopia\Response', $status);

        try{
            $this->response->setStatusCode(0); // Unknown status code
        }
        catch(\Exception $e) {
            $this->assertInstanceOf('\Exception', $e);
            return;
        }

        $this->fail('Expected exception');
    }

    public function testAddHeader()
    {
        $result = $this->response->addHeader('key', 'value');
        $this->assertEquals($this->response, $result);
    }

    public function testAddCookie()
    {
        $result = $this->response->addCookie('name', 'value');
        $this->assertEquals($this->response, $result);
    }

    public function testSend()
    {

        ob_start(); //Start of build

        @$this->response
            ->addHeader('key', 'value')
            ->addCookie('name', 'value')
            ->send('body'); //FIXME we have a problem with header printing

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('body', $html);
    }

    public function testRedirect()
    {
        ob_start(); //Start of build

        @$this->response->redirect('http://www.example.com');

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('', $html);

        ob_start(); //Start of build

        @$this->response->redirect('http://www.example.com', 300);

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('', $html);
    }

    public function testJson()
    {
        ob_start(); //Start of build

        @$this->response->json(['key' => 'value']);

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('{"key":"value"}', $html);
    }

    public function testJsonp()
    {
        ob_start(); //Start of build

        @$this->response->jsonp('test', ['key' => 'value']);

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('parent.test({"key":"value"});', $html);
    }

    public function testIframe()
    {
        ob_start(); //Start of build

        @$this->response->iframe('test', ['key' => 'value']);

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('<script type="text/javascript">window.parent.test({"key":"value"});</script>', $html);
    }
}
