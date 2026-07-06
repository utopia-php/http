<?php

declare(strict_types=1);

namespace Utopia\Http\Tests\Adapter\FPM;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Utopia\Http\Adapter\FPM\RequestFactory;

final class RequestFactoryTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        $this->resetGlobals();
    }

    protected function tearDown(): void
    {
        $this->resetGlobals();

        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testCreatesPsrServerRequestFromGlobals(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/users?active=1',
            'HTTP_HOST' => 'api.example.test',
            'REQUEST_SCHEME' => 'https',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUEST_ID' => 'request-1',
        ];
        $_GET = ['active' => '1'];
        $_COOKIE = ['session' => 'abc'];

        $request = new RequestFactory()->create();

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api.example.test/users?active=1', (string) $request->getUri());
        $this->assertSame($_SERVER, $request->getServerParams());
        $this->assertSame(['active' => '1'], $request->getQueryParams());
        $this->assertSame(['session' => 'abc'], $request->getCookieParams());
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('request-1', $request->getHeaderLine('X-Request-Id'));
        $this->assertNull($request->getParsedBody());
    }

    public function testBuildsRelativeUriWhenHostIsMissing(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/health',
        ];

        $request = new RequestFactory()->create();

        $this->assertSame('/health', (string) $request->getUri());
    }

    public function testPrefersForwardedProtoOverRequestSchemeAndHttpsFlag(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/secure',
            'HTTP_HOST' => 'api.example.test',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'REQUEST_SCHEME' => 'http',
            'HTTPS' => 'off',
        ];

        $request = new RequestFactory()->create();

        $this->assertSame('https://api.example.test/secure', (string) $request->getUri());
    }

    public function testIgnoresInvalidForwardedProto(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/secure',
            'HTTP_HOST' => 'api.example.test',
            'HTTP_X_FORWARDED_PROTO' => 'javascript',
            'REQUEST_SCHEME' => 'https',
        ];

        $request = new RequestFactory()->create();

        $this->assertSame('https://api.example.test/secure', (string) $request->getUri());
    }

    public function testUsesHttpsSchemeWhenHttpsFlagIsEnabled(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/secure',
            'HTTP_HOST' => 'api.example.test',
            'HTTPS' => 'on',
        ];

        $request = new RequestFactory()->create();

        $this->assertSame('https://api.example.test/secure', (string) $request->getUri());
    }

    public function testParsesFormBodyForWriteMethods(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/profile',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ];
        $_POST = ['name' => 'Ada', 'active' => '1'];

        $request = new RequestFactory(static fn(): string => 'name=Ada&active=1')->create();

        $this->assertSame(['name' => 'Ada', 'active' => '1'], $request->getParsedBody());
        $this->assertSame('name=Ada&active=1', (string) $request->getBody());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
    }

    public function testParsesJsonBodyForWriteMethods(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/profile',
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
        ];

        $request = new RequestFactory(static fn(): string => '{"name":"Ada","roles":["admin"]}')->create();

        $this->assertSame(['name' => 'Ada', 'roles' => ['admin']], $request->getParsedBody());
        $this->assertSame('{"name":"Ada","roles":["admin"]}', (string) $request->getBody());
    }

    public function testInvalidJsonBodyParsesAsEmptyArray(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/profile',
            'CONTENT_TYPE' => 'application/json',
        ];

        $request = new RequestFactory(static fn(): string => '{invalid')->create();

        $this->assertSame([], $request->getParsedBody());
    }

    public function testReadMethodsDoNotParseRequestBody(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/profile',
            'CONTENT_TYPE' => 'application/json',
        ];

        $request = new RequestFactory(static fn(): string => '{"ignored":true}')->create();

        $this->assertNull($request->getParsedBody());
        $this->assertSame('{"ignored":true}', (string) $request->getBody());
    }

    public function testNormalizesUploadedFiles(): void
    {
        $avatar = $this->temporaryFile('avatar');
        $document = $this->temporaryFile('document');

        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/upload',
        ];
        $_FILES = [
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

        $request = new RequestFactory()->create();
        $files = $request->getUploadedFiles();

        $this->assertInstanceOf(UploadedFileInterface::class, $files['avatar']);
        $this->assertSame('avatar.png', $files['avatar']->getClientFilename());
        $this->assertSame('image/png', $files['avatar']->getClientMediaType());
        $this->assertIsArray($files['documents']);
        $this->assertInstanceOf(UploadedFileInterface::class, $files['documents'][0]);
        $this->assertSame('document.pdf', $files['documents'][0]->getClientFilename());
    }

    public function testDefaultsMissingGlobals(): void
    {
        $request = new RequestFactory()->create();

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

    private function resetGlobals(): void
    {
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
    }
}
