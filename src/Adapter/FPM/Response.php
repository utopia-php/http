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

namespace Utopia\HTTP\Adapter\FPM;

use Utopia\HTTP\Response as HTTPResponse;

class Response extends HTTPResponse
{
    /**
     * Output response
     *
     * Generate HTTP response output including the response header (+cookies) and body and prints them.
     *
     * @param string $body
     * @param int $exit exit code or don't exit if code is null
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
                'expires'	=> $cookie['expire'],
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
        \setcookie($name, $value, $options);
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
}
