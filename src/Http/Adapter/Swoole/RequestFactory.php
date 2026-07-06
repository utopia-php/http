<?php

declare(strict_types=1);

namespace Utopia\Http\Adapter\Swoole;

use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleRequest;
use Utopia\Psr7\ServerRequest;
use Utopia\Psr7\Stream;
use Utopia\Psr7\UploadedFile;
use Utopia\Psr7\Uri;

/**
 * @internal
 */
final class RequestFactory
{
    private const array ALLOWED_SCHEMES = ['http', 'https', 'ws', 'wss'];

    public function create(SwooleRequest $request): ServerRequestInterface
    {
        $rawBody = $request->rawContent() ?: '';
        $headers = $this->headersFromSwoole($request);
        $server = $request->server ?? [];
        $method = $server['request_method'] ?? 'UNKNOWN';

        return new ServerRequest(
            method: (string) $method,
            uri: $this->uriFromSwoole($request, $headers),
            serverParams: $server,
            cookieParams: $request->cookie ?? [],
            queryParams: $request->get ?? [],
            uploadedFiles: UploadedFile::normalizeFiles($request->files ?? []),
            parsedBody: $this->parsedBody($request, (string) $method, $headers, $rawBody),
            body: new Stream($rawBody),
            headers: $headers,
        );
    }

    /**
     * @param array<string, string|array<int, string>> $headers
     * @return array<string, mixed>|null
     */
    private function parsedBody(SwooleRequest $request, string $method, array $headers, string $rawBody): ?array
    {
        if (!\in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $contentType = $headers['content-type'] ?? $headers['Content-Type'] ?? '';
        $contentType = \is_array($contentType) ? ($contentType[0] ?? '') : $contentType;
        $contentType = trim(explode(';', (string) $contentType)[0]);

        if ($contentType === 'application/json') {
            $decoded = json_decode($rawBody, true);

            return \is_array($decoded) ? $decoded : [];
        }

        return $request->post ?? [];
    }

    /**
     * @param array<string, string|array<int, string>> $headers
     */
    private function uriFromSwoole(SwooleRequest $request, array $headers): Uri
    {
        $server = $request->server ?? [];
        $requestUri = (string) ($server['request_uri'] ?? '/');
        $query = (string) ($server['query_string'] ?? '');

        if ($query !== '' && !str_contains($requestUri, '?')) {
            $requestUri .= '?' . $query;
        }

        $host = $headers['host'] ?? $headers['Host'] ?? '';
        $host = \is_array($host) ? ($host[0] ?? '') : $host;

        if ($host === '') {
            return Uri::parse($requestUri);
        }

        return Uri::parse($this->schemeFromSwoole($headers) . '://' . $host . $requestUri);
    }

    /**
     * @param array<string, string|array<int, string>> $headers
     */
    private function schemeFromSwoole(array $headers): string
    {
        $forwarded = $headers['x-forwarded-proto'] ?? $headers['X-Forwarded-Proto'] ?? null;
        $forwarded = \is_array($forwarded) ? ($forwarded[0] ?? null) : $forwarded;

        if (\in_array($forwarded, self::ALLOWED_SCHEMES, true)) {
            return (string) $forwarded;
        }

        return 'http';
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function headersFromSwoole(SwooleRequest $request): array
    {
        $headers = [];

        foreach ($request->header ?? [] as $name => $value) {
            $headers[(string) $name] = \is_array($value)
                ? array_values(array_map(strval(...), $value))
                : (string) $value;
        }

        return $headers;
    }
}
