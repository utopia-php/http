<?php

namespace Utopia;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    protected ?Response $response;

    public function setUp(): void
    {
        $this->response = new Response();
    }

    public function tearDown(): void
    {
        $this->response = null;
    }

    public function testCanSetContentType()
    {
        $contentType = $this->response->setContentType(Response::CONTENT_TYPE_HTML, Response::CHARSET_UTF8);

        // Assertions
        $this->assertInstanceOf('Utopia\Response', $contentType);
    }

    public function testCanSetStatus()
    {
        $status = $this->response->setStatusCode(Response::STATUS_CODE_OK);

        // Assertions
        $this->assertInstanceOf('Utopia\Response', $status);

        try {
            $this->response->setStatusCode(0); // Unknown status code
        } catch(\Exception $e) {
            $this->assertInstanceOf('\Exception', $e);

            return;
        }

        $this->fail('Expected exception');
    }

    public function testCanGetStatus()
    {
        $status = $this->response->setStatusCode(Response::STATUS_CODE_OK);

        // Assertions
        $this->assertInstanceOf('Utopia\Response', $status);
        $this->assertEquals(Response::STATUS_CODE_OK, $this->response->getStatusCode());
    }

    public function testCanAddHeader()
    {
        $result = $this->response->addHeader('key', 'value');
        $this->assertEquals($this->response, $result);
    }

    public function testCanAddCookie()
    {
        $result = $this->response->addCookie('name', 'value');
        $this->assertEquals($this->response, $result);

        //test cookie case insensitive
        $result = $this->response->addCookie('cookieName', 'cookieValue');
        $result->getCookies()['cookiename']['name'] = 'cookiename';
        $result->getCookies()['cookiename']['value'] = 'cookieValue';
    }

    public function testCanSend()
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

    public function testCanSendRedirect()
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

    public function testCanSendText()
    {
        ob_start(); //Start of build

        @$this->response->text('HELLO WORLD');

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('HELLO WORLD', $html);
        $this->assertEquals('text/plain; charset=UTF-8', $this->response->getContentType());
    }

    public function testCanSendHtml()
    {
        ob_start(); //Start of build

        @$this->response->html('<html></html>');

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('<html></html>', $html);
        $this->assertEquals('text/html; charset=UTF-8', $this->response->getContentType());
    }

    public function testCanSendJson()
    {
        ob_start(); //Start of build

        @$this->response->json(['key' => 'value']);

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('{"key":"value"}', $html);
        $this->assertEquals('application/json; charset=UTF-8', $this->response->getContentType());
    }

    public function testCanSendJsonp()
    {
        ob_start(); //Start of build

        @$this->response->jsonp('test', ['key' => 'value']);

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('parent.test({"key":"value"});', $html);
        $this->assertEquals('text/javascript; charset=UTF-8', $this->response->getContentType());
    }

    public function testCanSendIframe()
    {
        ob_start(); //Start of build

        @$this->response->iframe('test', ['key' => 'value']);

        $html = ob_get_contents();
        ob_end_clean(); //End of build

        $this->assertEquals('<script type="text/javascript">window.parent.test({"key":"value"});</script>', $html);
        $this->assertEquals('text/html; charset=UTF-8', $this->response->getContentType());
    }
}
