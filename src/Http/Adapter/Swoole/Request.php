<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Http\Request as SwooleRequest;
use Utopia\Http\Request as UtopiaRequest;

class Request extends UtopiaRequest
{
    /**
     * Swoole Request Object
     */
    protected SwooleRequest $swoole;

    /**
     * Request constructor.
     */
    public function __construct(SwooleRequest $request)
    {
        $this->swoole = $request;
    }

    /**
     * Get raw payload
     *
     * Method for getting the HTTP request payload as a raw string.
     */
    public function getRawPayload(): string
    {
        return $this->swoole->rawContent() ?: '';
    }

    /**
     * Get server
     *
     * Method for querying server parameters. If $key is not found $default value will be returned.
     */
    public function getServer(string $key, ?string $default = null): ?string
    {
        return $this->swoole->server[$key] ?? $default;
    }

    /**
     * Set server
     *
     * Method for setting server parameters.
     */
    public function setServer(string $key, string $value): static
    {
        $this->swoole->server[$key] = $value;

        return $this;
    }

    /**
     * Get IP
     *
     * Returns users IP address.
     * Support HTTP_X_FORWARDED_FOR header usually return
     *  from different proxy servers or PHP default REMOTE_ADDR
     */
    public function getIP(): string
    {
        $remoteAddr = $this->getServer('remote_addr') ?? '0.0.0.0';

        foreach ($this->trustedIpHeaders as $header) {
            $headerValue = $this->getHeader($header);

            if (empty($headerValue)) {
                continue;
            }

            // Leftmost IP address is the address of the originating client
            $ips = explode(',', $headerValue);
            $ip = trim($ips[0]);

            // Validate IP format (supports both IPv4 and IPv6)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return $remoteAddr;
    }

    /**
     * Get Protocol
     *
     * Returns request protocol.
     * Support HTTP_X_FORWARDED_PROTO header usually return
     *  from different proxy servers or PHP default REQUEST_SCHEME
     */
    public function getProtocol(): string
    {
        $protocol = $this->getHeader('x-forwarded-proto', $this->getServer('server_protocol') ?? 'https');

        if ($protocol === 'HTTP/1.1') {
            return 'http';
        }

        return match ($protocol) {
            'http', 'https', 'ws', 'wss' => $protocol,
            default => 'https',
        };
    }

    /**
     * Get Port
     *
     * Returns request port.
     */
    public function getPort(): string
    {
        return $this->getHeader('x-forwarded-port', (string) \parse_url($this->getProtocol() . '://' . $this->getHeader('x-forwarded-host', $this->getHeader('host')), PHP_URL_PORT));
    }

    /**
     * Get Hostname
     *
     * Returns request hostname.
     */
    public function getHostname(): string
    {
        $hostname = \parse_url($this->getProtocol() . '://' . $this->getHeader('x-forwarded-host', $this->getHeader('host')), PHP_URL_HOST);
        return strtolower(strval($hostname));
    }

    /**
     * Get Method
     *
     * Return HTTP request method
     */
    public function getMethod(): string
    {
        return $this->getServer('request_method') ?? 'UNKNOWN';
    }

    /**
     * Set method
     *
     * Set HTTP request method
     */
    public function setMethod(string $method): static
    {
        $this->setServer('request_method', $method);

        return $this;
    }

    /**
     * Get URI
     *
     * Return HTTP request URI
     */
    #[\Override]
    public function getURI(): string
    {
        return $this->getServer('request_uri') ?? '';
    }

    /**
     * Set URI
     *
     * Set HTTP request URI
     */
    public function setURI(string $uri): static
    {
        $this->setServer('request_uri', $uri);

        return $this;
    }

    /**
     * Get Referer
     *
     * Return HTTP referer header
     */
    public function getReferer(string $default = ''): string
    {
        return $this->getHeader('referer', '');
    }

    /**
     * Get Origin
     *
     * Return HTTP origin header
     */
    public function getOrigin(string $default = ''): string
    {
        return $this->getHeader('origin', $default);
    }

    /**
     * Get User Agent
     *
     * Return HTTP user agent header
     */
    public function getUserAgent(string $default = ''): string
    {
        return $this->getHeader('user-agent', $default);
    }

    /**
     * Get Accept
     *
     * Return HTTP accept header
     */
    public function getAccept(string $default = ''): string
    {
        return $this->getHeader('accept', $default);
    }

    /**
     * Get files
     *
     * Method for querying upload files data. If $key is not found empty array will be returned.
     *
     * @param  string  $key
     * @return array<string, mixed>
     */
    public function getFiles($key): array
    {
        $key = strtolower($key);

        return $this->swoole->files[$key] ?? [];
    }

    /**
     * Get cookie
     *
     * Method for querying HTTP cookie parameters. If $key is not found $default value will be returned.
     */
    public function getCookie(string $key, string $default = ''): string
    {
        $key = strtolower($key);

        return $this->swoole->cookie[$key] ?? $default;
    }

    /**
     * Get header
     *
     * Method for querying HTTP header parameters. If $key is not found $default value will be returned.
     */
    public function getHeader(string $key, string $default = ''): string
    {
        return $this->swoole->header[$key] ?? $default;
    }

    /**
     * Method for adding HTTP header parameters.
     */
    public function addHeader(string $key, string $value): static
    {
        $this->swoole->header[$key] = $value;

        return $this;
    }

    /**
     * Method for removing HTTP header parameters.
     */
    public function removeHeader(string $key): static
    {
        if (isset($this->swoole->header[$key])) {
            unset($this->swoole->header[$key]);
        }

        return $this;
    }

    public function getSwooleRequest(): SwooleRequest
    {
        return $this->swoole;
    }

    /**
     * Generate input
     *
     * Generate PHP input stream and parse it as an array in order to handle different content type of requests
     *
     * @return array<string, mixed>
     */
    protected function generateInput(): array
    {
        if (null === $this->queryString) {
            $this->queryString = $this->swoole->get ?? [];
        }
        if (null === $this->payload) {
            $contentType = $this->getHeader('content-type');

            // Get content-type without the charset
            $length = strpos($contentType, ';');
            $length = (empty($length)) ? strlen($contentType) : $length;
            $contentType = substr($contentType, 0, $length);

            $this->payload = match ($contentType) {
                'application/json' => json_decode(strval($this->swoole->rawContent()), true),
                default => $this->swoole->post,
            };

            if (empty($this->payload)) { // Make sure we return same data type even if json payload is empty or failed
                $this->payload = [];
            }
        }

        return match ($this->getMethod()) {
            self::METHOD_POST,
            self::METHOD_PUT,
            self::METHOD_PATCH,
            self::METHOD_DELETE => $this->payload,
            default => $this->queryString,
        };
    }

    /**
     * Generate headers
     *
     * Parse request headers as an array for easy querying using the getHeader method
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function generateHeaders(): array
    {
        return $this->swoole->header;
    }
}
