<?php

namespace Utopia\Http;

abstract class Request
{
    /**
     * HTTP methods
     */
    public const METHOD_OPTIONS = 'OPTIONS';

    public const METHOD_GET = 'GET';

    public const METHOD_HEAD = 'HEAD';

    public const METHOD_POST = 'POST';

    public const METHOD_PATCH = 'PATCH';

    public const METHOD_PUT = 'PUT';

    public const METHOD_DELETE = 'DELETE';

    public const METHOD_TRACE = 'TRACE';

    public const METHOD_CONNECT = 'CONNECT';

    /**
     * Container for php://input parsed stream as an associative array
     *
     * @var array|null
     */
    protected $payload = null;

    /**
     * Container for parsed query string params
     *
     * @var array|null
     */
    protected $queryString = null;

    /**
     * Container for parsed headers
     *
     * @var array|null
     */
    protected $headers = null;

    /**
     * Get Param
     *
     * Get param by current method name
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        $params = $this->getParams();

        return (isset($params[$key])) ? $params[$key] : $default;
    }

    /**
     * Get Params
     *
     * Get all params of current method
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->generateInput();
    }

    /**
     * Get Query
     *
     * Method for querying HTTP GET request parameters. If $key is not found $default value will be returned.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        $this->generateInput();

        return $this->queryString[$key] ?? $default;
    }

    /**
     * Set query string parameters
     *
     * @param  array  $params
     * @return static
     */
    public function setQuery(array $params): static
    {
        $this->queryString = $params;

        return $this;
    }

    /**
     * Get payload
     *
     * Method for querying HTTP request payload parameters. If $key is not found $default value will be returned.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getPayload(string $key, mixed $default = null): mixed
    {
        $this->generateInput();

        return $this->payload[$key] ?? $default;
    }

    /**
     * Get raw payload
     *
     * Method for getting the HTTP request payload as a raw string.
     *
     * @return string
     */
    abstract public function getRawPayload(): string;

    /**
     * Get server
     *
     * Method for querying server parameters. If $key is not found $default value will be returned.
     *
     * @param  string  $key
     * @param  string|null  $default
     * @return string|null
     */
    abstract public function getServer(string $key, ?string $default = null): ?string;

    /**
     * Set server
     *
     * Method for setting server parameters.
     *
     * @param  string  $key
     * @param  string  $value
     * @return static
     */
    abstract public function setServer(string $key, string $value): static;

    /**
     * Get IP
     *
     * Returns users IP address.
     * Support HTTP_X_FORWARDED_FOR header usually return
     *  from different proxy servers or PHP default REMOTE_ADDR
     *
     * @return string
     */
    abstract public function getIP(): string;

    /**
     * Get Protocol
     *
     * Returns request protocol.
     * Support HTTP_X_FORWARDED_PROTO header usually return
     *  from different proxy servers or PHP default REQUEST_SCHEME
     *
     * @return string
     */
    abstract public function getProtocol(): string;

    /**
     * Get Port
     *
     * Returns request port.
     *
     * @return string
     */
    abstract public function getPort(): string;

    /**
     * Get Hostname
     *
     * Returns request hostname.
     *
     * @return string
     */
    abstract public function getHostname(): string;

    /**
     * Get Method
     *
     * Return HTTP request method
     *
     * @return string
     */
    abstract public function getMethod(): string;

    /**
     * Set Method
     *
     * Set HTTP request method
     *
     * @param  string  $method
     * @return static
     */
    abstract public function setMethod(string $method): static;

    /**
     * Get URI
     *
     * Return HTTP request URI
     *
     * @return string
     */
    abstract public function getURI(): string;

    /**
     * Get Path
     *
     * Return HTTP request path
     *
     * @param  string  $uri
     * @return static
     */
    abstract public function setURI(string $uri): static;

    /**
     * Get query string
     *
     * Method for querying HTTP GET request query string
     *
     * @return string
     */
    abstract public function getQueryString(): string;

    /**
     * Set query string
     *
     * Method for setting HTTP GET request query string
     *
     * @param string $value
     * @return static
     */
    abstract public function setQueryString(string $value): static;

    /**
     * Get files
     *
     * Method for querying upload files data. If $key is not found empty array will be returned.
     *
     * @param  string  $key
     * @return array
     */
    abstract public function getFiles(string $key): array;

    /**
     * Get Referer
     *
     * Return HTTP referer header
     *
     * @param  string  $default
     * @return string
     */
    abstract public function getReferer(string $default = ''): string;

    /**
     * Get Origin
     *
     * Return HTTP origin header
     *
     * @param  string  $default
     * @return string
     */
    abstract public function getOrigin(string $default = ''): string;

    /**
     * Get User Agent
     *
     * Return HTTP user agent header
     *
     * @param  string  $default
     * @return string
     */
    abstract public function getUserAgent(string $default = ''): string;

    /**
     * Get Accept
     *
     * Return HTTP accept header
     *
     * @param  string  $default
     * @return string
     */
    abstract public function getAccept(string $default = ''): string;

    /**
     * Get cookie
     *
     * Method for querying HTTP cookie parameters. If $key is not found $default value will be returned.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    abstract public function getCookie(string $key, string $default = ''): string;

    /**
     * Get header
     *
     * Method for querying HTTP header parameters. If $key is not found $default value will be returned.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    abstract public function getHeader(string $key, string $default = ''): string;

    /**
     * Get headers
     *
     * Method for getting all HTTP header parameters.
     *
     * @return array<string,mixed>
     */
    public function getHeaders(): array
    {
        return $this->generateHeaders();
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
    abstract public function addHeader(string $key, string $value): static;

    /**
     * Remvoe header
     *
     * Method for removing HTTP header parameters.
     *
     * @param  string  $key
     * @return static
     */
    abstract public function removeHeader(string $key): static;

    /**
     * Get Request Size
     *
     * Returns request size in bytes
     *
     * @return int
     */
    public function getSize(): int
    {
        return \mb_strlen(\implode("\n", $this->generateHeaders()), '8bit') + \mb_strlen(\file_get_contents('php://input'), '8bit');
    }

    /**
     * Get Content Range Start
     *
     * Returns the start of content range
     *
     * @return int|null
     */
    public function getContentRangeStart(): ?int
    {
        $data = $this->parseContentRange();
        if (!empty($data)) {
            return $data['start'];
        } else {
            return null;
        }
    }

    /**
     * Get Content Range End
     *
     * Returns the end of content range
     *
     * @return int|null
     */
    public function getContentRangeEnd(): ?int
    {
        $data = $this->parseContentRange();
        if (!empty($data)) {
            return $data['end'];
        } else {
            return null;
        }
    }

    /**
     * Get Content Range Size
     *
     * Returns the size of content range
     *
     * @return int|null
     */
    public function getContentRangeSize(): ?int
    {
        $data = $this->parseContentRange();
        if (!empty($data)) {
            return $data['size'];
        } else {
            return null;
        }
    }

    /**
     * Get Content Range Unit
     *
     * Returns the unit of content range
     *
     * @return string|null
     */
    public function getContentRangeUnit(): ?string
    {
        $data = $this->parseContentRange();
        if (!empty($data)) {
            return $data['unit'];
        } else {
            return null;
        }
    }

    /**
     * Get Range Start
     *
     * Returns the start of range header
     *
     * @return int|null
     */
    public function getRangeStart(): ?int
    {
        $data = $this->parseRange();
        if (!empty($data)) {
            return $data['start'];
        }

        return null;
    }

    /**
     * Get Range End
     *
     * Returns the end of range header
     *
     * @return int|null
     */
    public function getRangeEnd(): ?int
    {
        $data = $this->parseRange();
        if (!empty($data)) {
            return $data['end'];
        }

        return null;
    }

    /**
     * Get Range Unit
     *
     * Returns the unit of range header
     *
     * @return string|null
     */
    public function getRangeUnit(): ?string
    {
        $data = $this->parseRange();
        if (!empty($data)) {
            return $data['unit'];
        }

        return null;
    }


    /**
     * Set payload parameters
     *
     * @param  array  $params
     * @return static
     */
    public function setPayload(array $params): static
    {
        $this->payload = $params;

        return $this;
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

    /**
     * Generate input
     *
     * Generate PHP input stream and parse it as an array in order to handle different content type of requests
     *
     * @return array
     */
    abstract protected function generateInput(): array;

    /**
     * Content Range Parser
     *
     * Parse content-range request header for easy access
     *
     * @return array|null
     */
    protected function parseContentRange(): ?array
    {
        $contentRange = $this->getHeader('content-range', '');
        $data = [];
        if (!empty($contentRange)) {
            $contentRange = explode(' ', $contentRange);
            if (count($contentRange) !== 2) {
                return null;
            }

            $data['unit'] = trim($contentRange[0]);

            if (empty($data['unit'])) {
                return null;
            }

            $rangeData = explode('/', $contentRange[1]);
            if (count($rangeData) !== 2) {
                return null;
            }

            if (!ctype_digit($rangeData[1])) {
                return null;
            }

            $data['size'] = (int) $rangeData[1];
            $parts = explode('-', $rangeData[0]);
            if (count($parts) != 2) {
                return null;
            }

            if (!ctype_digit($parts[0]) || !ctype_digit($parts[1])) {
                return null;
            }

            $data['start'] = (int) $parts[0];
            $data['end'] = (int) $parts[1];
            if ($data['start'] > $data['end'] || $data['end'] > $data['size']) {
                return null;
            }

            return $data;
        }

        return null;
    }

    /**
     * Range Parser
     *
     * Parse range request header for easy access
     *
     * @return array|null
     */
    protected function parseRange(): ?array
    {
        $rangeHeader = $this->getHeader('range', '');
        if (empty($rangeHeader)) {
            return null;
        }

        $data = [];
        $ranges = explode('=', $rangeHeader);
        if (count($ranges) !== 2 || empty($ranges[0]) || empty($ranges[1])) {
            return null;
        }
        $data['unit'] = $ranges[0];

        $ranges = explode('-', $ranges[1]);
        if (count($ranges) !== 2 || strlen($ranges[0]) === 0) {
            return null;
        }

        if (!ctype_digit($ranges[0])) {
            return null;
        }

        $data['start'] = (int) $ranges[0];

        if (strlen($ranges[1]) === 0) {
            $data['end'] = null;
        } else {
            if (!ctype_digit($ranges[1])) {
                return null;
            }
            $data['end'] = (int) $ranges[1];
        }

        if ($data['end'] !== null && $data['start'] >= $data['end']) {
            return null;
        }

        return $data;
    }
}
