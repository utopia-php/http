<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
 * @version 2.0
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia;

use Exception;

class Response
{
    /**
     * HTTP content types
     */
    const CONTENT_TYPE_TEXT         = 'text/plain';
    const CONTENT_TYPE_HTML         = 'text/html';
    const CONTENT_TYPE_JSON         = 'application/json';
    const CONTENT_TYPE_XML          = 'text/xml';
    const CONTENT_TYPE_JAVASCRIPT   = 'text/javascript';

    /**
     * HTTP response status codes
     */
    const STATUS_CODE_CONTINUE                          = 100;
    const STATUS_CODE_SWITCHING_PROTOCOLS               = 101;
    const STATUS_CODE_OK                                = 200;
    const STATUS_CODE_CREATED                           = 201;
    const STATUS_CODE_ACCEPTED                          = 202;
    const STATUS_CODE_NON_AUTHORITATIVE_INFORMATION     = 203;
    const STATUS_CODE_NOCONTENT                         = 204;
    const STATUS_CODE_RESETCONTENT                      = 205;
    const STATUS_CODE_PARTIALCONTENT                    = 206;
    const STATUS_CODE_MULTIPLE_CHOICES                  = 300;
    const STATUS_CODE_MOVED_PERMANENTLY                 = 301;
    const STATUS_CODE_FOUND                             = 302;
    const STATUS_CODE_SEE_OTHER                         = 303;
    const STATUS_CODE_NOT_MODIFIED                      = 304;
    const STATUS_CODE_USE_PROXY                         = 305;
    const STATUS_CODE_UNUSED                            = 306;
    const STATUS_CODE_TEMPORARY_REDIRECT                = 307;
    const STATUS_CODE_BAD_REQUEST                       = 400;
    const STATUS_CODE_UNAUTHORIZED                      = 401;
    const STATUS_CODE_PAYMENT_REQUIRED                  = 402;
    const STATUS_CODE_FORBIDDEN                         = 403;
    const STATUS_CODE_NOT_FOUND                         = 404;
    const STATUS_CODE_METHOD_NOT_ALLOWED                = 405;
    const STATUS_CODE_NOT_ACCEPTABLE                    = 406;
    const STATUS_CODE_PROXY_AUTHENTICATION_REQUIRED     = 407;
    const STATUS_CODE_REQUEST_TIMEOUT                   = 408;
    const STATUS_CODE_CONFLICT                          = 409;
    const STATUS_CODE_GONE                              = 410;
    const STATUS_CODE_LENGTH_REQUIRED                   = 411;
    const STATUS_CODE_PRECONDITION_FAILED               = 412;
    const STATUS_CODE_REQUEST_ENTITY_TOO_LARGE          = 413;
    const STATUS_CODE_REQUEST_URI_TOO_LONG              = 414;
    const STATUS_CODE_UNSUPPORTED_MEDIA_TYPE            = 415;
    const STATUS_CODE_REQUESTED_RANGE_NOT_SATISFIABLE   = 416;
    const STATUS_CODE_EXPECTATION_FAILED                = 417;
    const STATUS_CODE_TOO_MANY_REQUESTS                 = 429;
    const STATUS_CODE_INTERNAL_SERVER_ERROR             = 500;
    const STATUS_CODE_NOT_IMPLEMENTED                   = 501;
    const STATUS_CODE_BAD_GATEWAY                       = 502;
    const STATUS_CODE_SERVICE_UNAVAILABLE               = 503;
    const STATUS_CODE_GATEWAY_TIMEOUT                   = 504;
    const STATUS_CODE_HTTP_VERSION_NOT_SUPPORTED        = 505;

    /**
     * @var array
     */
    private $statusCodes = Array(
        self::STATUS_CODE_CONTINUE                         => 'Continue',
        self::STATUS_CODE_SWITCHING_PROTOCOLS              => 'Switching Protocols',
        self::STATUS_CODE_OK                               => 'OK',
        self::STATUS_CODE_CREATED                          => 'Created',
        self::STATUS_CODE_ACCEPTED                         => 'Accepted',
        self::STATUS_CODE_NON_AUTHORITATIVE_INFORMATION    => 'Non-Authoritative Information',
        self::STATUS_CODE_NOCONTENT                        => 'No Content',
        self::STATUS_CODE_RESETCONTENT                     => 'Reset Content',
        self::STATUS_CODE_PARTIALCONTENT                   => 'Partial Content',
        self::STATUS_CODE_MULTIPLE_CHOICES                 => 'Multiple Choices',
        self::STATUS_CODE_MOVED_PERMANENTLY                => 'Moved Permanently',
        self::STATUS_CODE_FOUND                            => 'Found',
        self::STATUS_CODE_SEE_OTHER                        => 'See Other',
        self::STATUS_CODE_NOT_MODIFIED                     => 'Not Modified',
        self::STATUS_CODE_USE_PROXY                        => 'Use Proxy',
        self::STATUS_CODE_UNUSED                           => '(Unused)',
        self::STATUS_CODE_TEMPORARY_REDIRECT               => 'Temporary Redirect',
        self::STATUS_CODE_BAD_REQUEST                      => 'Bad Request',
        self::STATUS_CODE_UNAUTHORIZED                     => 'Unauthorized',
        self::STATUS_CODE_PAYMENT_REQUIRED                 => 'Payment Required',
        self::STATUS_CODE_FORBIDDEN                        => 'Forbidden',
        self::STATUS_CODE_NOT_FOUND                        => 'Not Found',
        self::STATUS_CODE_METHOD_NOT_ALLOWED               => 'Method Not Allowed',
        self::STATUS_CODE_NOT_ACCEPTABLE                   => 'Not Acceptable',
        self::STATUS_CODE_PROXY_AUTHENTICATION_REQUIRED    => 'Proxy Authentication Required',
        self::STATUS_CODE_REQUEST_TIMEOUT                  => 'Request Timeout',
        self::STATUS_CODE_CONFLICT                         => 'Conflict',
        self::STATUS_CODE_GONE                             => 'Gone',
        self::STATUS_CODE_LENGTH_REQUIRED                  => 'Length Required',
        self::STATUS_CODE_PRECONDITION_FAILED              => 'Precondition Failed',
        self::STATUS_CODE_REQUEST_ENTITY_TOO_LARGE         => 'Request Entity Too Large',
        self::STATUS_CODE_REQUEST_URI_TOO_LONG             => 'Request-URI Too Long',
        self::STATUS_CODE_UNSUPPORTED_MEDIA_TYPE           => 'Unsupported Media Type',
        self::STATUS_CODE_REQUESTED_RANGE_NOT_SATISFIABLE  => 'Requested Range Not Satisfiable',
        self::STATUS_CODE_EXPECTATION_FAILED               => 'Expectation Failed',
        self::STATUS_CODE_TOO_MANY_REQUESTS                => 'Too Many Requests',
        self::STATUS_CODE_INTERNAL_SERVER_ERROR            => 'Internal Server Error',
        self::STATUS_CODE_NOT_IMPLEMENTED                  => 'Not Implemented',
        self::STATUS_CODE_BAD_GATEWAY                      => 'Bad Gateway',
        self::STATUS_CODE_SERVICE_UNAVAILABLE              => 'Service Unavailable',
        self::STATUS_CODE_GATEWAY_TIMEOUT                  => 'Gateway Timeout',
        self::STATUS_CODE_HTTP_VERSION_NOT_SUPPORTED       => 'HTTP Version Not Supported',
    );

    const COOKIE_SAMESITE_NONE      = 'None';
    const COOKIE_SAMESITE_STRICT    = 'Strict';
    const COOKIE_SAMESITE_LAX       = 'Lax';

    /**
     * @var int
     */
    private $statusCode = self::STATUS_CODE_OK;

    /**
     * @var string
     */
    private $contentType = self::CONTENT_TYPE_HTML;

    /**
     * @var bool
     */
    private $disablePayload = false;

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var array
     */
    private $cookies = array();

    /**
     * @var int
     */
    private $startTime = 0;

    /**
     * @var int
     */
    private $size = 0;

    /**
     * Response constructor.
     */
    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Set content type
     *
     * Set HTTP content type header.
     *
     * @param  string   $type
     * @return self
     */
    public function setContentType($type)
    {
        $this->contentType = $type;

        return $this;
    }

    /**
     * Set status code
     *
     * Set HTTP response status code between available options. if status code is unknown an exception will be thrown
     *
     * @param int $code
     * @return self
     * @throws Exception
     */
    public function setStatusCode($code = 200)
    {
        if (!array_key_exists($code, $this->statusCodes)) {
            throw new Exception('Unknown HTTP status code');
        }

        $this->statusCode = $code;

        return $this;
    }

    /**
     * Get Response Size
     *
     * Return output response size in bytes
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Don't allow payload on response output
     */
    public function disablePayload() {
        $this->disablePayload = true;
        return $this;
    }

    /**
     * Allow payload on response output
     */
    public function enablePayload() {
        $this->disablePayload = false;
        return $this;
    }

    /**
     * Add header
     *
     * Add an HTTP response header
     *
     * @param string $key
     * @param string $value
     * @return self
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Remove header
     *
     * Remove HTTP response header
     *
     * @param string $key
     * @return self
     */
    public function removeHeader($key)
    {
        if(isset($this->headers[$key])) {
            unset($this->headers[$key]);
        }

        return $this;
    }

    /**
     * Get Cookies
     *
     * Return array of all response cookies
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Add cookie
     *
     * Add an HTTP cookie to response header
     *
     * @param string $name
     * @param string $value    [optional]
     * @param int    $expire   [optional]
     * @param string $path     [optional]
     * @param string $domain   [optional]
     * @param bool   $secure   [optional]
     * @param bool   $httponly [optional]
     * @param string $sameSite [optional]
     * @return self
     */
    public function addCookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null, $sameSite = null)
    {
        $this->cookies[$name] = array(
            'name'		=> $name,
            'value'		=> $value,
            'expire'	=> $expire,
            'path' 		=> $path,
            'domain' 	=> $domain,
            'secure' 	=> $secure,
            'httponly'	=> $httponly,
            'samesite'	=> $sameSite,
        );

        return $this;
    }

    /**
     * Remove cookie
     *
     * Remove HTTP response cookie
     *
     * @param string $name
     * @return self
     */
    public function removeCookie($name)
    {
        if(isset($this->headers[$name])) {
            unset($this->cookies[$name]);
        }

        return $this;
    }

    /**
     * Get Cookies
     *
     * Return array of all response cookies
     *
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Output response
     *
     * Generate HTTP response output including the response header (+cookies) and body and prints them.
     *
     * @param string $body
     * @param int $exit exit code or don't exit if code is null
     *
     * @return self
     */
    public function send($body = '', $exit = null)
    {
        if(!$this->disablePayload) {
            $this->addHeader('X-Debug-Speed', microtime(true) - $this->startTime);

            $this
                ->appendCookies()
                ->appendHeaders()
            ;

            $this->size = $this->size + mb_strlen(implode("\n", headers_list())) + mb_strlen($body, '8bit');

            echo $body;

            $this->disablePayload();
        }

        if(!is_null($exit)) {
            exit($exit); // Exit with code
        }
    }

    /**
     * Append headers
     *
     * Iterating over response headers to generate them using native PHP header function.
     * This method is also responsible for generating the response and content type headers.
     *
     * @return self
     */
    private function appendHeaders()
    {
        // Send status code header
        http_response_code($this->statusCode);

        // Send content type header
        $this
            ->addHeader('Content-Type', $this->contentType . '; charset=UTF-8')
        ;

        // Set application headers
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }

        return $this;
    }

    /**
     * Append cookies
     *
     * Iterating over response cookies to generate them using native PHP cookie function.
     *
     * @return self
     */
    private function appendCookies()
    {
        foreach ($this->cookies as $cookie) {
            
            if (version_compare(PHP_VERSION, '7.3.0', '<')) {
                setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
            }
            else {
                setcookie($cookie['name'], $cookie['value'], [
                    'expires' => $cookie['expire'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httponly'],
                    'samesite' => $cookie['samesite'],
                ]);
            }
        }

        return $this;
    }

    /**
     * Redirect
     *
     * This helper is for sending a 30* HTTP response.
     * After setting relevant HTTP headers for redirect response this helper stop application native flow what means the shutdown method will not be executed
     *
     * NOTICE: it seems webkit based browsers have problems redirecting link with 300 status codes.
     *
     * @see https://code.google.com/p/chromium/issues/detail?id=75540
     * @see https://bugs.webkit.org/show_bug.cgi?id=47425
     *
     * @param string $url complete absolute URI for redirection as required by the internet standard RFC 2616 (HTTP 1.1)
     * @param int $statusCode valid HTTP/1.1 status code
     *
     * @param null $exit
     * @throws Exception
     * @see http://tools.ietf.org/html/rfc2616
     */
    public function redirect($url, $statusCode = 301, $exit = null)
    {
        if (300 == $statusCode) {
            trigger_error('It seems webkit based browsers have problems redirecting link with 300 status codes!', E_USER_NOTICE);
        }

        $this
            ->addHeader('Location', $url)
            ->setStatusCode($statusCode)
            ->send('', $exit)
        ;
    }

    /**
     * Text
     *
     * This helper is for sending plain text HTTP response and sets relevant content type header ('text/plain').
     *
     * @see http://en.wikipedia.org/wiki/JSON
     *
     * @param string $data
     */
    public function text($data)
    {
        $this
            ->setContentType(Response::CONTENT_TYPE_TEXT)
            ->send($data)
        ;
    }

    /**
     * JSON
     *
     * This helper is for sending JSON HTTP response.
     * It sets relevant content type header ('application/json') and convert a PHP array ($data) to valid JSON using native json_encode
     *
     * @see http://en.wikipedia.org/wiki/JSON
     *
     * @param array $data
     */
    public function json(array $data)
    {
        $this
            ->setContentType(Response::CONTENT_TYPE_JSON)
            ->send(json_encode($data, JSON_UNESCAPED_UNICODE))
        ;
    }

    /**
     * JSON with padding
     *
     * This helper is for sending JSONP HTTP response.
     * It sets relevant content type header ('text/javascript') and convert a PHP array ($data) to valid JSON using native json_encode
     *
     * @see http://en.wikipedia.org/wiki/JSONP
     *
     * @param string $callback
     * @param array  $data
     */
    public function jsonp($callback, array $data)
    {
        $this
            ->setContentType(Response::CONTENT_TYPE_JAVASCRIPT)
            ->send('parent.' . $callback . '(' . json_encode($data) . ');')
        ;
    }

    /**
     * Iframe
     *
     * This helper is for sending iframe HTTP response.
     * It sets relevant content type header ('text/html') and convert a PHP array ($data) to valid JSON using native json_encode
     *
     * @param string $callback
     * @param array  $data
     */
    public function iframe($callback, array $data)
    {
        $this
            ->send('<script type="text/javascript">window.parent.' . $callback . '(' . json_encode($data) . ');</script>');
    }

    /**
     * No Content
     *
     * This helper is for sending no content HTTP response.
     *
     * The server has successfully fulfilled the request
     *  and that there is no additional content to send in the response payload body.
     */
    public function noContent()
    {
        $this
            ->setStatusCode(self::STATUS_CODE_NOCONTENT)
            ->send('');
    }
}