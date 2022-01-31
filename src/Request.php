<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\HTTP;

abstract class Request
{
    /**
     * HTTP methods
     */
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_POST    = 'POST';
    const METHOD_PATCH   = 'PATCH';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';

    /**
     * Container for php://input parsed stream
     *
     * @var array|null
     */
    protected $payload = null;

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
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    abstract public function getParam(string $key, mixed $default = null): mixed;

    /**
     * Get Params
     *
     * Get all params of current method
     *
     * @return array
     */
    abstract public function getParams(): array;

    /**
     * Get Query
     *
     * Method for querying HTTP GET request parameters. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    abstract public function getQuery(string $key, mixed $default = null): mixed;

    /**
     * Get payload
     *
     * Method for querying HTTP request payload parameters. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    abstract public function getPayload(string $key, mixed $default = null): mixed;

    /**
     * Get server
     *
     * Method for querying server parameters. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    abstract public function getServer(string $key, mixed $default = null): mixed;

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
     * Get URI
     *
     * Return HTTP request URI
     *
     * @return string
     */
    abstract public function getURI(): string;

    /**
     * Get files
     *
     * Method for querying upload files data. If $key is not found empty array will be returned.
     *
     * @param  string $key
     * @return array
     */
    abstract public function getFiles(string $key): array;

    /**
     * Get Referer
     *
     * Return HTTP referer header
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function getReferer(string $default = ''): string;

    /**
     * Get Origin
     *
     * Return HTTP origin header
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function getOrigin(string $default = ''): string;

    /**
     * Get User Agent
     *
     * Return HTTP user agent header
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function getUserAgent(string $default = ''): string;

    /**
     * Get Accept
     *
     * Return HTTP accept header
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function getAccept(string $default = ''): string;

    /**
     * Get cookie
     *
     * Method for querying HTTP cookie parameters. If $key is not found $default value will be returned.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    abstract public function getCookie(string $key, string $default = ''): string;

    /**
     * Get header
     *
     * Method for querying HTTP header parameters. If $key is not found $default value will be returned.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    abstract public function getHeader(string $key, string $default = ''): string;

    /**
     * Get Request Size
     *
     * Returns request size in bytes
     *
     * @return int
     */
    abstract public function getSize(): int;

    /**
     * Generate headers
     *
     * Parse request headers as an array for easy querying using the getHeader method
     *
     * @return array
     */
    abstract protected function generateHeaders(): array;

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
        if(!empty($data)) {
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
        if(!empty($data)) {
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
        if(!empty($data)) {
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
        if(!empty($data)) {
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
        if(!empty($data)) {
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
        if(!empty($data)) {
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
        if(!empty($data)) {
            return $data['unit'];
        }
        return null;
    }

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

        if(!ctype_digit($ranges[0])) {
            return null;
        }

        $data['start'] = (int) $ranges[0];

        if (strlen($ranges[1]) === 0) {
            $data['end'] =  null;
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
