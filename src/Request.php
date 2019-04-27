<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/eldadfux/Utopia-PHP-Framework
 * @author Eldad Fux <eldad@fuxie.co.il>
 * @version 2.0
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
     * @var array
     */
    private $payload = null;

    /**
     * Container for parsed headers
     *
     * @var array
     */
    private $headers = null;

    /**
     * Get Param
     *
     * Get param by current method name
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        switch($this->getServer('REQUEST_METHOD', '')) {
            case self::METHOD_GET:
                return $this->getQuery($key, $default);
                break;
            case self::METHOD_POST:
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
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
    public function getParams()
    {
        switch($this->getServer('REQUEST_METHOD', '')) {
            case self::METHOD_GET:
                return $_GET;
                break;
            case self::METHOD_POST:
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
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
    public function getQuery($key, $default = null)
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
    public function getPayload($key, $default = null)
    {
        $payload = $this->generateInput();

        return (isset($payload[$key])) ? $payload[$key] : $default;
    }

    /**
     * Get request
     *
     * Method for querying HTTP request parameters whether carried with GET, POST or cookie. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed $default
     * @param array $priority The later will overwrite $key value if set
     * @throws Exception
     * @return mixed
     */
    public function getRequest($key, $default = null, $priority = ['get', 'post', 'cookie', 'header'])
    {
        $value = $default;

        foreach ($priority as $method) {
            switch ($method) {
                case 'get':
                    $temp = $this->getQuery($key, null);
                    $value = (null !== $temp) ? $temp : $value;
                    break;
                case 'post':
                    $temp = $this->getPayload($key, null);
                    $value = (null !== $temp) ? $temp : $value;
                    break;
                case 'cookie':
                    $temp = $this->getCookie($key, null);
                    $value = (null !== $temp) ? $temp : $value;
                    break;
                case 'header':
                    $temp = $this->getHeader($key, null);
                    $value = (null !== $temp) ? $temp : $value;
                    break;
                default:
                    throw new Exception($method . ' method not supported');
            }
        }

        return $value;
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
    public function getServer($key, $default = null)
    {
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * Get IP
     *
     * Returns users IP address.
     * Support HTTP_X_FORWARDED_FOR header usually return
     *  from different proxy servers or PHP default REMOTE_ADDR
     */
    public function getIP() {
        return $this->getServer('HTTP_X_FORWARDED_FOR', $this->getServer('REMOTE_ADDR', '0.0.0.0'));
    }

    /**
     * Get Method
     *
     * Return HTTP request method
     *
     * @return string
     */
    public function getMethod() {
        return $this->getServer('REQUEST_METHOD', 'UNKNOWN');
    }

    /**
     * Get files
     *
     * Method for querying upload files data. If $key is not found empty array will be returned.
     *
     * @param  string $key
     * @return array
     */
    public function getFiles($key)
    {
        return (isset($_FILES[$key])) ? $_FILES[$key] : array();
    }

    /**
     * Get cookie
     *
     * Method for querying HTTP cookie parameters. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getCookie($key, $default = null)
    {
        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }

    /**
     * Get header
     *
     * Method for querying HTTP header parameters. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getHeader($key, $default = null)
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
    public function getSize()
    {
        return mb_strlen(implode("\n", $this->generateHeaders()), '8bit') + mb_strlen(file_get_contents('php://input'), '8bit');
    }

    /**
     * Generate input
     *
     * Generate PHP input stream and parse it as an array in order to handle different content type of requests
     *
     * @return array
     */
    private function generateInput()
    {
        if (null === $this->payload) {
            $contentType    = $this->getHeader('Content-Type');

            // Get content-type without the charset
            $length         = strpos($contentType, ';');
            $length         = (empty($length)) ? strlen($contentType) : $length;
            $contentType    = substr($contentType, 0, $length);

            switch ($contentType) {
                case 'application/json':
                    $this->payload = json_decode(file_get_contents('php://input'), true);
                    break;

                default:
                    $this->payload = $_POST;
                    break;
            }

            if(empty($this->payload)) { // Make sure we return same data type even if json payload is empty or failed
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
    private function generateHeaders()
    {
        if (null === $this->headers) {
            $fallback = null;

            /**
             * Fallback for older PHP versions
             * that do not support generateHeaders
             */
            if (!function_exists('getallheaders')) {
                $headers = [];

                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }

                $this->headers = $headers;

                return $this->headers;
            }

            $this->headers = getallheaders();
        }

        return $this->headers;
    }
}