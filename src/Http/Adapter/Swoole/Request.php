<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Http\Request as SwooleRequest;
use Utopia\Http\Request as UtopiaRequest;

class Request extends UtopiaRequest
{
    /**
     * Swoole Request Object
     *
     * @var SwooleRequest
     */
    public SwooleRequest $swoole;

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
     *
     * @return string
     */
    public function getRawPayload(): string
    {
        return $this->swoole->rawContent();
    }

    /**
     * Get server
     *
     * Method for querying server parameters. If $key is not found $default value will be returned.
     *
     * @param  string  $key
     * @param  string|null  $default
     * @return string|null
     */
    public function getServer(string $key, ?string $default = null): ?string
    {
        return $this->swoole->server[$key] ?? $default;
    }

    /**
     * Set server
     *
     * Method for setting server parameters.
     *
     * @param  string  $key
     * @param  string  $value
     * @return static
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
        $ips = explode(',', $this->getHeader('x-forwarded-for', $this->getServer('remote_addr') ?? '0.0.0.0'));

        return trim($ips[0] ?? '');
    }

    /**
     * Get Protocol
     *
     * Returns request protocol.
     * Support HTTP_X_FORWARDED_PROTO header usually return
     *  from different proxy servers or PHP default REQUEST_SCHEME
     *
     * @return string
     */
    public function getProtocol(): string
    {
        $protocol = $this->getHeader('x-forwarded-proto', $this->getServer('server_protocol') ?? 'https');

        if ($protocol === 'HTTP/1.1') {
            return 'http';
        }

        return match ($protocol) {
            'http', 'https', 'ws', 'wss' => $protocol,
            default => 'https'
        };
    }

    /**
     * Get Port
     *
     * Returns request port.
     *
     * @return string
     */
    public function getPort(): string
    {
        return $this->getHeader('x-forwarded-port', (string) \parse_url($this->getProtocol().'://'.$this->getHeader('x-forwarded-host', $this->getHeader('host')), PHP_URL_PORT));
    }

    /**
     * Get Hostname
     *
     * Returns request hostname.
     *
     * @return string
     */
    public function getHostname(): string
    {
        return strval(\parse_url($this->getProtocol().'://'.$this->getHeader('x-forwarded-host', $this->getHeader('host')), PHP_URL_HOST));
    }

    /**
     * Get Method
     *
     * Return HTTP request method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->getServer('request_method') ?? 'UNKNOWN';
    }

    /**
     * Set method
     *
     * Set HTTP request method
     *
     * @param  string  $method
     * @return static
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
     *
     * @return string
     */
    public function getURI(): string
    {
        return $this->getServer('request_uri') ?? '';
    }

    /**
     * Set URI
     *
     * Set HTTP request URI
     *
     * @param  string  $uri
     * @return static
     */
    public function setURI(string $uri): static
    {
        $this->setServer('request_uri', $uri);

        return $this;
    }

    /**
     * Get Query String
     *
     * Return HTTP request query string
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->getServer('query_string') ?? '';
    }

    /**
     * Set Query String
     *
     * Set HTTP request query string
     *
     * @param  string  $value
     * @return static
     */
    public function setQueryString(string $value): static
    {
        $this->setServer('query_string', $value);

        return $this;
    }

    /**
     * Get Referer
     *
     * Return HTTP referer header
     *
     * @return string
     */
    public function getReferer(string $default = ''): string
    {
        return $this->getHeader('referer', '');
    }

    /**
     * Get Origin
     *
     * Return HTTP origin header
     *
     * @return string
     */
    public function getOrigin(string $default = ''): string
    {
        return $this->getHeader('origin', $default);
    }

    /**
     * Get User Agent
     *
     * Return HTTP user agent header
     *
     * @return string
     */
    public function getUserAgent(string $default = ''): string
    {
        return $this->getHeader('user-agent', $default);
    }

    /**
     * Get Accept
     *
     * Return HTTP accept header
     *
     * @return string
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
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    public function getCookie(string $key, string $default = ''): string
    {
        return $this->swoole->cookie[$key] ?? $this->swoole->cookie[strtolower($key)] ?? $default;
    }

    /**
     * Get header
     *
     * Method for querying HTTP header parameters. If $key is not found $default value will be returned.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    public function getHeader(string $key, string $default = ''): string
    {
        return $this->swoole->header[$key] ?? $default;
    }

    /**
     * Method for adding HTTP header parameters.
     *
     * @param  string  $key
     * @param  string  $value
     * @return static
     */
    public function addHeader(string $key, string $value): static
    {
        $this->swoole->header[$key] = $value;

        return $this;
    }

    /**
     * Method for removing HTTP header parameters.
     *
     * @param  string  $key
     * @return static
     */
    public function removeHeader(string $key): static
    {
        if (isset($this->swoole->header[$key])) {
            unset($this->swoole->header[$key]);
        }

        return $this;
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

            switch ($contentType) {
                case 'application/json':
                    $this->payload = json_decode(strval($this->swoole->rawContent()), true);
                    break;

                default:
                    $this->payload = $this->swoole->post;
                    break;
            }

            if (empty($this->payload)) { // Make sure we return same data type even if json payload is empty or failed
                $this->payload = [];
            }
        }

        return match ($this->getMethod()) {
            self::METHOD_POST,
            self::METHOD_PUT,
            self::METHOD_PATCH,
            self::METHOD_DELETE => $this->payload,
            default => $this->queryString
        };
    }

    /**
     * Generate headers
     *
     * Parse request headers as an array for easy querying using the getHeader method
     *
     * @return array<string, mixed>
     */
    protected function generateHeaders(): array
    {
        $headers = $this->swoole->header ?? [];

        // Check if cookies are available in a separate property
        if (!empty($this->swoole->cookie)) {
            // Convert cookies back to Cookie header format
            $cookiePairs = [];
            foreach ($this->swoole->cookie as $name => $value) {
                $cookiePairs[] = $name . '=' . $value;
            }
            if (!empty($cookiePairs)) {
                $headers['cookie'] = implode('; ', $cookiePairs);
            }
        }

        foreach ($headers as $key => $value) {
            $headers[strtolower($key)] = $value;
        }

        return $headers;
    }
}
