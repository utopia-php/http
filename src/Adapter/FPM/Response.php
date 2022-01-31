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
    public function send(string $body = '', int $exit = null): void
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
            $this->size = $this->size + \mb_strlen(\implode("\n", \headers_list())) + \mb_strlen($body, '8bit');

            echo $body;

            $this->disablePayload();
        }

        if (!\is_null($exit)) {
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
    protected function appendHeaders(): self
    {
        // Send status code header
        \http_response_code($this->statusCode);

        // Send content type header
        if (!empty($this->contentType)) {
            $this
                ->addHeader('Content-Type', $this->contentType)
            ;
        }

        // Set application headers
        foreach ($this->headers as $key => $value) {
            \header($key . ': ' . $value);
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
            if (\version_compare(PHP_VERSION, '7.3.0', '<')) {
                \setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
            } else {
                \setcookie($cookie['name'], $cookie['value'], [
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
}
