<?php

namespace Utopia\Http\Adapter\FPM;

use Utopia\Http\Request as UtopiaRequest;

class Request extends UtopiaRequest
{
    /**
     * Container for raw php://input parsed stream
     *
     * @var string
     */
    protected $rawPayload = '';

    /**
     * Get raw payload
     *
     * Method for getting the HTTP request payload as a raw string.
     *
     * @return string
     */
    public function getRawPayload(): string
    {
        $this->generateInput();

        return $this->rawPayload;
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
        return $_SERVER[$key] ?? $default;
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
        $_SERVER[$key] = $value;

        return $this;
    }

    /**
     * Get IP
     *
     * Returns users IP address.
     * Support HTTP_X_FORWARDED_FOR header usually return
     *  from different proxy servers or PHP default REMOTE_ADDR
     *
     * @return string
     */
    public function getIP(): string
    {
        $ips = explode(',', $this->getHeader('HTTP_X_FORWARDED_FOR', $this->getServer('REMOTE_ADDR') ?? '0.0.0.0'));

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
        return $this->getServer('HTTP_X_FORWARDED_PROTO', $this->getServer('REQUEST_SCHEME')) ?? 'https';
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
        return (string) \parse_url($this->getProtocol().'://'.$this->getServer('HTTP_HOST', ''), PHP_URL_PORT);
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
        return (string) \parse_url($this->getProtocol().'://'.$this->getServer('HTTP_HOST', ''), PHP_URL_HOST);
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
        return $this->getServer('REQUEST_METHOD') ?? 'UNKNOWN';
    }

    /**
     * Set Method
     *
     * Set HTTP request method
     *
     * @param  string  $method
     * @return static
     */
    public function setMethod(string $method): static
    {
        $this->setServer('REQUEST_METHOD', $method);

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
        return $this->getServer('REQUEST_URI') ?? '';
    }

    /**
     * Get Path
     *
     * Return HTTP request path
     *
     * @param  string  $uri
     * @return static
     */
    public function setURI(string $uri): static
    {
        $this->setServer('REQUEST_URI', $uri);

        return $this;
    }

    /**
     * Get files
     *
     * Method for querying upload files data. If $key is not found empty array will be returned.
     *
     * @param  string  $key
     * @return array
     */
    public function getFiles(string $key): array
    {
        return (isset($_FILES[$key])) ? $_FILES[$key] : [];
    }

    /**
     * Get Referer
     *
     * Return HTTP referer header
     *
     * @param  string  $default
     * @return string
     */
    public function getReferer(string $default = ''): string
    {
        return (string) $this->getServer('HTTP_REFERER', $default);
    }

    /**
     * Get Origin
     *
     * Return HTTP origin header
     *
     * @param  string  $default
     * @return string
     */
    public function getOrigin(string $default = ''): string
    {
        return (string) $this->getServer('HTTP_ORIGIN', $default);
    }

    /**
     * Get User Agent
     *
     * Return HTTP user agent header
     *
     * @param  string  $default
     * @return string
     */
    public function getUserAgent(string $default = ''): string
    {
        return (string) $this->getServer('HTTP_USER_AGENT', $default);
    }

    /**
     * Get Accept
     *
     * Return HTTP accept header
     *
     * @param  string  $default
     * @return string
     */
    public function getAccept(string $default = ''): string
    {
        return (string) $this->getServer('HTTP_ACCEPT', $default);
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
        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
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
        $headers = $this->generateHeaders();

        return (isset($headers[$key])) ? $headers[$key] : $default;
    }

    /**
     * Set header
     *
     * Method for adding HTTP header parameters.
     *
     * @param  string  $key
     * @param  string  $value
     * @return static
     */
    public function addHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Remvoe header
     *
     * Method for removing HTTP header parameters.
     *
     * @param  string  $key
     * @return static
     */
    public function removeHeader(string $key): static
    {
        if (isset($this->headers[$key])) {
            unset($this->headers[$key]);
        }

        return $this;
    }

    /**
     * Generate input
     *
     * Generate PHP input stream and parse it as an array in order to handle different content type of requests
     *
     * @return array
     */
    protected function generateInput(): array
    {
        if (null === $this->queryString) {
            $this->queryString = $_GET;
        }
        if (null === $this->payload) {
            $contentType = $this->getHeader('content-type');

            // Get content-type without the charset
            $length = \strpos($contentType, ';');
            $length = (empty($length)) ? \strlen($contentType) : $length;
            $contentType = \substr($contentType, 0, $length);

            $this->rawPayload = \file_get_contents('php://input');

            switch ($contentType) {
                case 'application/json':
                    $this->payload = \json_decode($this->rawPayload, true);
                    break;
                default:
                    $this->payload = $_POST;
                    break;
            }

            if (empty($this->payload)) { // Make sure we return same data type even if json payload is empty or failed
                $this->payload = [];
            }
        }

        return match ($this->getServer('REQUEST_METHOD', '')) {
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
     * @return array
     */
    protected function generateHeaders(): array
    {
        if (null === $this->headers) {
            /**
             * Fallback for older PHP versions
             * that do not support generateHeaders
             */
            if (!\function_exists('getallheaders')) {
                $headers = [];

                foreach ($_SERVER as $name => $value) {
                    if (\substr($name, 0, 5) == 'HTTP_') {
                        $headers[\str_replace(' ', '-', \strtolower(\str_replace('_', ' ', \substr($name, 5))))] = $value;
                    }
                }

                $this->headers = $headers;

                return $this->headers;
            }

            $this->headers = array_change_key_case(getallheaders());
        }

        return $this->headers;
    }
}
