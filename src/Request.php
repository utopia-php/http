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

namespace Utopia;

class Request
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
    public function getParam(string $key, mixed $default = null)
    {
        switch ($this->getServer('REQUEST_METHOD', '')) {
            case self::METHOD_GET:
                return $this->getQuery($key, $default);
                break;
            case self::METHOD_POST:
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
            case self::METHOD_DELETE:
                return $this->getPayload($key, $default);
                break;
            default:
                return $this->getQuery($key, $default);
        }
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
        switch ($this->getServer('REQUEST_METHOD', '')) {
            case self::METHOD_GET:
                return $_GET;
                break;
            case self::METHOD_POST:
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
            case self::METHOD_DELETE:
                return $this->generateInput();
                break;
            default:
                return $_GET;
        }
    }

    /**
     * Get Query
     *
     * Method for querying HTTP GET request parameters. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return (isset($_GET[$key])) ? $_GET[$key] : $default;
    }

    /**
     * Get payload
     *
     * Method for querying HTTP request payload parameters. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getPayload(string $key, mixed $default = null): mixed
    {
        $payload = $this->generateInput();

        return (isset($payload[$key])) ? $payload[$key] : $default;
    }

    /**
     * Get server
     *
     * Method for querying server parameters. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getServer(string $key, mixed $default = null): mixed
    {
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
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
        $ips = explode(',',$this->getHeader('HTTP_X_FORWARDED_FOR', $this->getServer('REMOTE_ADDR', '0.0.0.0')));
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
        return $this->getServer('HTTP_X_FORWARDED_PROTO', $this->getServer('REQUEST_SCHEME', 'https'));
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
        return $this->getServer('REQUEST_METHOD', 'UNKNOWN');
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
        return $this->getServer('REQUEST_URI', '');
    }

    /**
     * Get files
     *
     * Method for querying upload files data. If $key is not found empty array will be returned.
     *
     * @param  string $key
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
     * @param string $default
     *
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
     * @param string $default
     *
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
     * @param string $default
     *
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
     * @param string $default
     *
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
     * @param string $key
     * @param string $default
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
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getHeader(string $key, string $default = ''): string
    {
        $headers = $this->generateHeaders();

        return (isset($headers[$key])) ? $headers[$key] : $default;
    }

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
     * Generate input
     *
     * Generate PHP input stream and parse it as an array in order to handle different content type of requests
     *
     * @return array
     */
    protected function generateInput(): array
    {
        if (null === $this->payload) {
            $contentType    = $this->getHeader('content-type');

            // Get content-type without the charset
            $length         = \strpos($contentType, ';');
            $length         = (empty($length)) ? \strlen($contentType) : $length;
            $contentType    = \substr($contentType, 0, $length);

            switch ($contentType) {
                case 'application/json':
                    $this->payload = \json_decode(\file_get_contents('php://input'), true);
                    break;

                default:
                    $this->payload = $_POST;
                    break;
            }

            if (empty($this->payload)) { // Make sure we return same data type even if json payload is empty or failed
                $this->payload = [];
            }
        }

        return $this->payload;
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
