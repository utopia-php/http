<?php

declare(strict_types=1);

namespace Utopia\Http\Tests\Adapter\Swoole;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Swoole\Http\Request as SwooleRequest;
use Utopia\Http\Adapter\Swoole\RequestFactory;

final class RequestFactoryTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testCreatesPsrServerRequestFromSwooleRequest(): void
    {
        $swoole = new TestSwooleRequest();
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/users',
            'query_string' => 'active=1',
            'server_protocol' => 'HTTP/1.1',
        ];
        $swoole->header = [
            'host' => 'api.example.test',
            'accept' => 'application/json',
            'x-request-id' => 'request-1',
        ];
        $swoole->get = ['active' => '1'];
        $swoole->cookie = ['session' => 'abc'];

        $request = new RequestFactory()->create($swoole);

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://api.example.test/users?active=1', (string) $request->getUri());
        $this->assertSame($swoole->server, $request->getServerParams());
        $this->assertSame(['active' => '1'], $request->getQueryParams());
        $this->assertSame(['session' => 'abc'], $request->getCookieParams());
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('request-1', $request->getHeaderLine('X-Request-Id'));
        $this->assertNull($request->getParsedBody());
    }

    public function testDoesNotAppendQueryStringWhenRequestUriAlreadyContainsQuery(): void
    {
        $swoole = new TestSwooleRequest();
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/users?active=1',
            'query_string' => 'ignored=1',
            'server_protocol' => 'HTTP/1.1',
        ];
        $swoole->header = ['host' => 'api.example.test'];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame('http://api.example.test/users?active=1', (string) $request->getUri());
    }

    public function testBuildsRelativeUriWhenHostIsMissing(): void
    {
        $swoole = new TestSwooleRequest();
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/health',
            'server_protocol' => 'HTTP/1.1',
        ];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame('/health', (string) $request->getUri());
    }

    public function testPrefersForwardedProtoHeaderForUriScheme(): void
    {
        $swoole = new TestSwooleRequest();
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/secure',
            'server_protocol' => 'HTTP/1.1',
        ];
        $swoole->header = [
            'host' => 'api.example.test',
            'x-forwarded-proto' => 'https',
        ];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame('https://api.example.test/secure', (string) $request->getUri());
    }

    public function testIgnoresInvalidForwardedProto(): void
    {
        $swoole = new TestSwooleRequest();
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/secure',
            'server_protocol' => 'HTTP/1.1',
        ];
        $swoole->header = [
            'host' => 'api.example.test',
            'x-forwarded-proto' => 'javascript',
        ];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame('http://api.example.test/secure', (string) $request->getUri());
    }

    public function testFallsBackToHttpWithoutForwardedProto(): void
    {
        $swoole = new TestSwooleRequest();
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/secure',
            'server_protocol' => 'HTTP/2',
        ];
        $swoole->header = ['host' => 'api.example.test'];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame('http://api.example.test/secure', (string) $request->getUri());
    }

    public function testPreservesArrayHeaderValues(): void
    {
        $swoole = new TestSwooleRequest();
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/',
        ];
        $swoole->header = [
            'host' => 'api.example.test',
            'x-tag' => ['alpha', 'beta'],
        ];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame(['alpha', 'beta'], $request->getHeader('X-Tag'));
        $this->assertSame('alpha, beta', $request->getHeaderLine('X-Tag'));
    }

    public function testParsesFormBodyForWriteMethods(): void
    {
        $swoole = new TestSwooleRequest('name=Ada&active=1');
        $swoole->server = [
            'request_method' => 'PUT',
            'request_uri' => '/profile',
        ];
        $swoole->header = ['content-type' => 'application/x-www-form-urlencoded'];
        $swoole->post = ['name' => 'Ada', 'active' => '1'];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame(['name' => 'Ada', 'active' => '1'], $request->getParsedBody());
        $this->assertSame('name=Ada&active=1', (string) $request->getBody());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
    }

    public function testParsesJsonBodyForWriteMethods(): void
    {
        $swoole = new TestSwooleRequest('{"name":"Ada","roles":["admin"]}');
        $swoole->server = [
            'request_method' => 'PATCH',
            'request_uri' => '/profile',
        ];
        $swoole->header = ['content-type' => 'application/json; charset=utf-8'];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame(['name' => 'Ada', 'roles' => ['admin']], $request->getParsedBody());
        $this->assertSame('{"name":"Ada","roles":["admin"]}', (string) $request->getBody());
    }

    public function testInvalidJsonBodyParsesAsEmptyArray(): void
    {
        $swoole = new TestSwooleRequest('{invalid');
        $swoole->server = [
            'request_method' => 'POST',
            'request_uri' => '/profile',
        ];
        $swoole->header = ['content-type' => 'application/json'];

        $request = new RequestFactory()->create($swoole);

        $this->assertSame([], $request->getParsedBody());
    }

    public function testReadMethodsDoNotParseRequestBody(): void
    {
        $swoole = new TestSwooleRequest('{"ignored":true}');
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/profile',
        ];
        $swoole->header = ['content-type' => 'application/json'];
        $swoole->post = ['ignored' => 'true'];

        $request = new RequestFactory()->create($swoole);

        $this->assertNull($request->getParsedBody());
        $this->assertSame('{"ignored":true}', (string) $request->getBody());
    }

    public function testNormalizesUploadedFiles(): void
    {
        $avatar = $this->temporaryFile('avatar');
        $document = $this->temporaryFile('document');

        $swoole = new TestSwooleRequest();
        $swoole->server = [
            'request_method' => 'POST',
            'request_uri' => '/upload',
        ];
        $swoole->files = [
            'avatar' => [
                'tmp_name' => $avatar,
                'size' => 6,
                'error' => UPLOAD_ERR_OK,
                'name' => 'avatar.png',
                'type' => 'image/png',
            ],
            'documents' => [
                'tmp_name' => [$document],
                'size' => [8],
                'error' => [UPLOAD_ERR_OK],
                'name' => ['document.pdf'],
                'type' => ['application/pdf'],
            ],
        ];

        $request = new RequestFactory()->create($swoole);
        $files = $request->getUploadedFiles();

        $this->assertInstanceOf(UploadedFileInterface::class, $files['avatar']);
        $this->assertSame('avatar.png', $files['avatar']->getClientFilename());
        $this->assertSame('image/png', $files['avatar']->getClientMediaType());
        $this->assertIsArray($files['documents']);
        $this->assertInstanceOf(UploadedFileInterface::class, $files['documents'][0]);
        $this->assertSame('document.pdf', $files['documents'][0]->getClientFilename());
    }

    public function testDefaultsMissingSwooleProperties(): void
    {
        $request = new RequestFactory()->create(new TestSwooleRequest());

        $this->assertSame('UNKNOWN', $request->getMethod());
        $this->assertSame('/', (string) $request->getUri());
        $this->assertSame([], $request->getServerParams());
        $this->assertSame([], $request->getCookieParams());
        $this->assertSame([], $request->getQueryParams());
        $this->assertSame([], $request->getUploadedFiles());
    }

    private function temporaryFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'utopia-http-upload-');
        $this->assertIsString($file);
        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;

        return $file;
    }
}

final class TestSwooleRequest extends SwooleRequest
{
    public function __construct(
        private readonly string|false $body = '',
    ) {}

    public function rawContent(): string|false
    {
        return $this->body;
    }
}
