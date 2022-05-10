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
    const CONTENT_TYPE_IMAGE        = 'image/*';
    const CONTENT_TYPE_IMAGE_JPEG   = 'image/jpeg';
    const CONTENT_TYPE_IMAGE_PNG    = 'image/png';
    const CONTENT_TYPE_IMAGE_GIF    = 'image/gif';
    const CONTENT_TYPE_IMAGE_SVG    = 'image/svg+xml';
    const CONTENT_TYPE_IMAGE_WEBP   = 'image/webp';
    const CONTENT_TYPE_IMAGE_ICON   = 'image/x-icon';
    const CONTENT_TYPE_IMAGE_BMP    = 'image/bmp';

    /**
     * Chrsets
     */
    const CHARSET_UTF8 = 'UTF-8';

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
    const STATUS_CODE_TOO_EARLY                         = 425;
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
    protected $statusCodes = [
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
        self::STATUS_CODE_TOO_EARLY                        => 'Too Early',
        self::STATUS_CODE_TOO_MANY_REQUESTS                => 'Too Many Requests',
        self::STATUS_CODE_INTERNAL_SERVER_ERROR            => 'Internal Server Error',
        self::STATUS_CODE_NOT_IMPLEMENTED                  => 'Not Implemented',
        self::STATUS_CODE_BAD_GATEWAY                      => 'Bad Gateway',
        self::STATUS_CODE_SERVICE_UNAVAILABLE              => 'Service Unavailable',
        self::STATUS_CODE_GATEWAY_TIMEOUT                  => 'Gateway Timeout',
        self::STATUS_CODE_HTTP_VERSION_NOT_SUPPORTED       => 'HTTP Version Not Supported',
    ];

    /**
     * Mime Types with compression support
     *
     * @var array
     */
    protected $compressed = [
        'text/plain' => true,
        'text/css' => true,
        'text/javascript' => true,
        'application/javascript' => true,
        'text/html' => true,
        'text/html; charset=UTF-8' => true,
        'application/json' => true,
        'application/json; charset=UTF-8' => true,
        'image/svg+xml' => true,
        'application/xml+rss' => true,
    ];

    const COOKIE_SAMESITE_NONE      = 'None';
    const COOKIE_SAMESITE_STRICT    = 'Strict';
    const COOKIE_SAMESITE_LAX       = 'Lax';

    const CHUNK_SIZE = 2000000; //2mb

    /**
     * @var int
     */
    protected int $statusCode = self::STATUS_CODE_OK;

    /**
     * @var string
     */
    protected string $contentType = '';

    /**
     * @var bool
     */
    protected bool $disablePayload = false;

    /**
     * @var bool
     */
    protected bool $sent = false;

    /**
     * @var array
     */
    protected array $headers = [];

    /**
     * @var array
     */
    protected array $cookies = [];

    /**
     * @var float
     */
    protected float $startTime = 0;

    /**
     * @var int
     */
    protected int $size = 0;

    /**
     * Response constructor.
     *
     * @param float $time response start time
     */
    public function __construct(float $time = 0)
    {
        $this->startTime = (!empty($time)) ? $time : \microtime(true);
    }

    /**
     * Set content type
     *
     * Set HTTP content type header.
     *
     * @param string $type
     * @param string $charset
     * @return self
     */
    public function setContentType(string $type, string $charset = ''): self
    {
        $this->contentType = $type . ((!empty($charset) ? '; charset='.$charset : ''));

        return $this;
    }

    /**
     * Get content type
     *
     * Get HTTP content type header.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
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
    public function setStatusCode(int $code = 200): self
    {
        if (!\array_key_exists($code, $this->statusCodes)) {
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
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Don't allow payload on response output
     */
    public function disablePayload(): self
    {
        $this->disablePayload = true;

        return $this;
    }

    /**
     * Allow payload on response output
     */
    public function enablePayload(): self
    {
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
    public function addHeader(string $key, string $value): self
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
    public function removeHeader(string $key): self
    {
        if (isset($this->headers[$key])) {
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
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Add cookie
     *
     * Add an HTTP cookie to response header
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     * @param string $sameSite
     * @return self
     */
    public function addCookie(string $name, string $value = null, int $expire = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null, string $sameSite = null): self
    {
        $name = strtolower($name);
        $this->cookies[$name] = [
            'name'		=> $name,
            'value'		=> $value,
            'expire'	=> $expire,
            'path' 		=> $path,
            'domain' 	=> $domain,
            'secure' 	=> $secure,
            'httponly'	=> $httponly,
            'samesite'	=> $sameSite,
        ];

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
    public function removeCookie(string $name): self
    {
        if (isset($this->headers[$name])) {
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
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Output response
     *
     * Generate HTTP response output including the response header (+cookies) and body and prints them.
     *
     * @param string $body
     *
     * @return void
     */
    public function send(string $body = ''): void
    {
        if($this->sent) {
            return;
        }

        $this->sent = true;

        $this->addHeader('X-Debug-Speed', (string)(\microtime(true) - $this->startTime));

        $this
            ->appendCookies()
            ->appendHeaders()
        ;

        if (!$this->disablePayload) {
            $length = strlen($body);

            $this->size = $this->size + strlen(implode("\n", $this->headers)) + $length;

            if(array_key_exists(
                $this->contentType,
                $this->compressed
                ) && ($length <= self::CHUNK_SIZE)) { // Dont compress with GZIP / Brotli if header is not listed and size is bigger than 2mb
                $this->end($body);
            }
            else {
                for ($i=0; $i < ceil($length / self::CHUNK_SIZE); $i++) {
                    $this->write(substr($body, ($i * self::CHUNK_SIZE), min(self::CHUNK_SIZE, $length - ($i * self::CHUNK_SIZE))));
                }

                $this->end();
            }

            $this->disablePayload();
        }
        else {
            $this->end();
        }
    }

    /**
     * Write
     *
     * Send output
     *
     * @param string $content
     *
     * @return void
     */
    protected function write(string $content): void
    {
        echo $content;
    }

    /**
     * End
     *
     * Send optional content and end
     *
     * @param string $content
     *
     * @return void
     */
    protected function end(string $content = null): void
    {
        if(!is_null($content)) {
            echo $content;
        }
    }

    /**
     * Output response
     *
     * Generate HTTP response output including the response header (+cookies) and body and prints them.
     *
     * @param string $body
     * @param bool $last
     *
     * @return void
     */
    public function chunk(string $body = '', bool $end = false): void
    {
        if ($this->sent) {
            return;
        }

        if ($end) {
            $this->sent = true;
        }

        $this->addHeader('X-Debug-Speed', (string) (microtime(true) - $this->startTime));

        $this
            ->appendCookies()
            ->appendHeaders()
        ;

        if (!$this->disablePayload) {
            $this->write($body);
            if ($end) {
                $this->disablePayload();
                $this->end();
            }
        } else {
            $this->end();
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
    protected function appendHeaders(): self
    {
        // Send status code header
        $this->sendStatus($this->statusCode);

        // Send content type header
        if (!empty($this->contentType)) {
            $this->addHeader('Content-Type', $this->contentType);
        }

        // Set application headers
        foreach ($this->headers as $key => $value) {
            $this->sendHeader($key, $value);
        }

        return $this;
    }

    /**
     * Send Status Code
     *
     * @param int $statusCode
     *
     * @return void
     */
    protected function sendStatus(int $statusCode): void
    {
        http_response_code($statusCode);
    }

    /**
     * Send Header
     *
     * Output Header
     *
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    protected function sendHeader(string $key, string $value): void
    {
        \header($key . ': ' . $value);
    }

    /**
     * Send Cookie
     *
     * Output Cookie
     *
     * @param string $name
     * @param string $value
     * @param array $options
     *
     * @return void
     */
    protected function sendCookie(string $name, string $value, array $options): void
    {
        // Use proper PHP keyword name
        $options['expires'] = $options['expire'];
        unset($options['expire']);

        // Set the cookie
        \setcookie($name, $value, $options);
    }

    /**
     * Append cookies
     *
     * Iterating over response cookies to generate them using native PHP cookie function.
     *
     * @return self
     */
    protected function appendCookies(): self
    {
        foreach ($this->cookies as $cookie) {
            $this->sendCookie($cookie['name'], $cookie['value'], [
                'expire'	=> $cookie['expire'],
                'path' 		=> $cookie['path'],
                'domain' 	=> $cookie['domain'],
                'secure' 	=> $cookie['secure'],
                'httponly'	=> $cookie['httponly'],
                'samesite'	=> $cookie['samesite'],
            ]);
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
     * @param int $statusCode valid HTTP status code
     * @param null $exit
     *
     * @throws Exception
     * @see http://tools.ietf.org/html/rfc2616
     *
     * @return void
     */
    public function redirect(string $url, int $statusCode = 301): void
    {
        if (300 == $statusCode) {
            \trigger_error('It seems webkit based browsers have problems redirecting link with 300 status codes!', E_USER_NOTICE);
        }

        $this
            ->addHeader('Location', $url)
            ->setStatusCode($statusCode)
            ->send('')
        ;
    }

    /**
     * HTML
     *
     * This helper is for sending an HTML HTTP response and sets relevant content type header ('text/html').
     *
     * @see http://en.wikipedia.org/wiki/JSON
     *
     * @param string $data
     *
     * @return void
     */
    public function html(string $data): void
    {
        $this
            ->setContentType(self::CONTENT_TYPE_HTML, self::CHARSET_UTF8)
            ->send($data)
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
     *
     * @return void
     */
    public function text(string $data): void
    {
        $this
            ->setContentType(self::CONTENT_TYPE_TEXT, self::CHARSET_UTF8)
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
     * @param mixed $data
     *
     * @return void
     */
    public function json($data): void
    {
        if (!is_array($data) && !$data instanceof \stdClass) {
            throw new \Exception('Invalid JSON input var');
        }

        $this
            ->setContentType(Response::CONTENT_TYPE_JSON, self::CHARSET_UTF8)
            ->send(\json_encode($data, JSON_UNESCAPED_UNICODE))
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
     * @param array $data
     *
     * @return void
     */
    public function jsonp(string $callback, array $data): void
    {
        $this
            ->setContentType(self::CONTENT_TYPE_JAVASCRIPT, self::CHARSET_UTF8)
            ->send('parent.' . $callback . '(' . \json_encode($data) . ');')
        ;
    }

    /**
     * Iframe
     *
     * This helper is for sending iframe HTTP response.
     * It sets relevant content type header ('text/html') and convert a PHP array ($data) to valid JSON using native json_encode
     *
     * @param string $callback
     * @param array $data
     *
     * @return void
     */
    public function iframe(string $callback, array $data): void
    {
        $this
            ->setContentType(self::CONTENT_TYPE_HTML, self::CHARSET_UTF8)
            ->send('<script type="text/javascript">window.parent.' . $callback . '(' . \json_encode($data) . ');</script>')
        ;
    }

    /**
     * No Content
     *
     * This helper is for sending no content HTTP response.
     *
     * The server has successfully fulfilled the request
     *  and that there is no additional content to send in the response payload body.
     *
     * @return void
     */
    public function noContent(): void
    {
        $this
            ->setStatusCode(self::STATUS_CODE_NOCONTENT)
            ->send('')
        ;
    }
}
