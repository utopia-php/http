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

    public function testStreamWithCallable()
    {
        $data = str_repeat('A', 100);

        ob_start();

        @$this->response->stream(function (int $offset, int $length) use ($data) {
            return substr($data, $offset, $length);
        }, strlen($data));

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($data, $output);
        $this->assertTrue($this->response->isSent());
        $this->assertEquals(strlen($data), $this->response->getSize());
    }

    public function testStreamWithGenerator()
    {
        $chunks = ['Hello ', 'World', '!'];
        $expected = implode('', $chunks);

        $generator = (function () use ($chunks) {
            foreach ($chunks as $chunk) {
                yield $chunk;
            }
        })();

        ob_start();

        @$this->response->stream($generator, strlen($expected));

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($expected, $output);
        $this->assertTrue($this->response->isSent());
        $this->assertEquals(strlen($expected), $this->response->getSize());
    }

    public function testStreamWithGeneratorLargeData()
    {
        $chunkSize = 1000000; // 1MB chunks
        $numChunks = 3;
        $totalSize = $chunkSize * $numChunks;

        $generator = (function () use ($chunkSize, $numChunks) {
            for ($i = 0; $i < $numChunks; $i++) {
                yield str_repeat(chr(65 + $i), $chunkSize);
            }
        })();

        ob_start();

        @$this->response->stream($generator, $totalSize);

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($totalSize, strlen($output));
        $this->assertEquals(str_repeat('A', $chunkSize), substr($output, 0, $chunkSize));
        $this->assertEquals(str_repeat('B', $chunkSize), substr($output, $chunkSize, $chunkSize));
        $this->assertEquals(str_repeat('C', $chunkSize), substr($output, $chunkSize * 2, $chunkSize));
        $this->assertEquals($totalSize, $this->response->getSize());
    }

    public function testStreamWithCallableMultipleChunks()
    {
        // Data larger than CHUNK_SIZE to test offset/length loop
        $data = str_repeat('X', Response::CHUNK_SIZE + 500);

        ob_start();

        @$this->response->stream(function (int $offset, int $length) use ($data) {
            return substr($data, $offset, $length);
        }, strlen($data));

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($data, $output);
        $this->assertEquals(strlen($data), $this->response->getSize());
    }

    public function testStreamDoesNotSendTwice()
    {
        $generator = (function () {
            yield 'first';
        })();

        ob_start();

        @$this->response->stream($generator, 5);

        // Try streaming again — should be a no-op
        $secondGenerator = (function () {
            yield 'second';
        })();

        @$this->response->stream($secondGenerator, 6);

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('first', $output);
    }

    public function testStreamWithDisabledPayload()
    {
        $this->response->disablePayload();

        $generator = (function () {
            yield 'should not appear';
        })();

        ob_start();

        @$this->response->stream($generator, 20);

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('', $output);
        $this->assertTrue($this->response->isSent());
    }

    public function testStreamWithEmptyGenerator()
    {
        /** @var \Generator<int, string, mixed, void> $generator */
        $generator = (function (): \Generator {
            yield from [];
        })();

        ob_start();

        @$this->response->stream($generator, 0);

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('', $output);
        $this->assertTrue($this->response->isSent());
        $this->assertEquals(0, $this->response->getSize());
    }

    public function testStreamSetsContentLengthHeader()
    {
        $data = 'test content';

        $generator = (function () use ($data) {
            yield $data;
        })();

        ob_start();

        @$this->response->stream($generator, strlen($data));

        ob_end_clean();

        $headers = $this->response->getHeaders();
        $this->assertEquals((string) strlen($data), $headers['Content-Length']);
    }
}
