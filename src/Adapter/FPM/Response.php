<?php

namespace Utopia\Adapter\FPM;

use Utopia\Response as UtopiaResponse;

class Response extends UtopiaResponse
{
    /**
     * Write
     *
     * Send output
     *
     * @param  string  $content
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
     * @param  string  $content
     * @return void
     */
    protected function end(string $content = null): void
    {
        if (! is_null($content)) {
            echo $content;
        }
    }


    /**
     * Send Status Code
     *
     * @param  int  $statusCode
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
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    protected function sendHeader(string $key, string $value): void
    {
        \header($key.': '.$value);
    }

    /**
     * Send Cookie
     *
     * Output Cookie
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $options
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
}
