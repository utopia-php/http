<?php

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Utopia\Http\Adapter\FPM\Response;

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
        $this->assertInstanceOf('Utopia\Http\Response', $contentType);
    }

    public function testCanSetStatus()
    {
        $status = $this->response->setStatusCode(Response::STATUS_CODE_OK);

        // Assertions
        $this->assertInstanceOf('Utopia\Http\Response', $status);

        try {
            $this->response->setStatusCode(0); // Unknown status code
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Exception', $e);

            return;
        }

        $this->fail('Expected exception');
    }

    public function testCanGetStatus()
    {
        $status = $this->response->setStatusCode(Response::STATUS_CODE_OK);

        // Assertions
        $this->assertInstanceOf('Utopia\Http\Response', $status);
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

    public function testStreamOutputsFullBody()
    {
        $data = 'Hello, this is streamed content!';
        $totalSize = strlen($data);

        ob_start();

        @$this->response->stream(
            function (int $offset, int $length) use ($data) {
                return substr($data, $offset, $length);
            },
            $totalSize
        );

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame($data, $output);
    }

    public function testStreamSetsContentLengthHeader()
    {
        $data = 'Test content';
        $totalSize = strlen($data);

        ob_start();

        @$this->response->stream(
            function (int $offset, int $length) use ($data) {
                return substr($data, $offset, $length);
            },
            $totalSize
        );

        ob_end_clean();

        $headers = $this->response->getHeaders();
        $this->assertSame((string) $totalSize, $headers['Content-Length']);
    }

    public function testStreamDoesNotSendWhenAlreadySent()
    {
        $data = 'First send';

        ob_start();
        @$this->response->send($data);
        $firstOutput = ob_get_contents();
        ob_end_clean();

        $this->assertSame($data, $firstOutput);

        // stream() should be a no-op since response is already sent
        ob_start();

        @$this->response->stream(
            function (int $offset, int $length) {
                return 'Should not appear';
            },
            17
        );

        $secondOutput = ob_get_contents();
        ob_end_clean();

        $this->assertSame('', $secondOutput);
    }

    public function testStreamWithDisabledPayload()
    {
        $this->response->disablePayload();

        ob_start();

        @$this->response->stream(
            function (int $offset, int $length) {
                return 'Should not appear';
            },
            17
        );

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame('', $output);
        $this->assertTrue($this->response->isSent());
    }

    public function testStreamReaderCalledWithCorrectOffsets()
    {
        // Create data larger than CHUNK_SIZE (2MB) to test multi-chunk streaming
        $chunkSize = Response::CHUNK_SIZE;
        $totalSize = $chunkSize * 2 + 500; // 2 full chunks + partial
        $data = str_repeat('A', $totalSize);

        $calls = [];

        ob_start();

        @$this->response->stream(
            function (int $offset, int $length) use ($data, &$calls) {
                $calls[] = ['offset' => $offset, 'length' => $length];
                return substr($data, $offset, $length);
            },
            $totalSize
        );

        $output = ob_get_contents();
        ob_end_clean();

        // Verify correct number of chunks
        $this->assertCount(3, $calls);

        // First chunk: offset=0, length=CHUNK_SIZE
        $this->assertSame(0, $calls[0]['offset']);
        $this->assertSame($chunkSize, $calls[0]['length']);

        // Second chunk: offset=CHUNK_SIZE, length=CHUNK_SIZE
        $this->assertSame($chunkSize, $calls[1]['offset']);
        $this->assertSame($chunkSize, $calls[1]['length']);

        // Third chunk: offset=2*CHUNK_SIZE, length=500
        $this->assertSame($chunkSize * 2, $calls[2]['offset']);
        $this->assertSame(500, $calls[2]['length']);

        // Verify complete output
        $this->assertSame($totalSize, strlen($output));
    }

    public function testStreamMarksResponseAsSent()
    {
        $this->assertFalse($this->response->isSent());

        ob_start();

        @$this->response->stream(
            function (int $offset, int $length) {
                return str_repeat('x', $length);
            },
            100
        );

        ob_end_clean();

        $this->assertTrue($this->response->isSent());
    }

    public function testChunkDoesNotDuplicateHeaders()
    {
        ob_start();

        // First chunk should append headers
        @$this->response->chunk('Hello ');
        // Second chunk should NOT append headers again
        @$this->response->chunk('World!', true);

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame('Hello World!', $output);
        $this->assertTrue($this->response->isSent());
    }

    public function testChunkIgnoresCallsAfterSent()
    {
        ob_start();

        @$this->response->chunk('First', true);
        @$this->response->chunk('Second', true); // should be ignored

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame('First', $output);
    }
}
