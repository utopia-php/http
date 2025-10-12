<?php

namespace Utopia\Http\Adapter\FPM;

use Utopia\Http\Response as UtopiaResponse;

class Response extends UtopiaResponse
{
    /**
     * Write
     *
     * Send output
     *
     * @param  string  $content
     * @return bool False if write cannot complete, such as request ended by client
     */
    public function write(string $content): bool
    {
        echo $content;
        return true;
    }

    /**
     * End
     *
     * Send optional content and end
     *
     * @param  string|null  $content
     * @return void
     */
    public function end(?string $content = null): void
    {
        if (!empty($content)) {
            echo $content;
        }
    }


    /**
     * Send Status Code
     *
     * @param  int  $statusCode
     * @param  string  $reason
     * @return void
     */
    protected function sendStatus(int $statusCode, string $reason): void
    {
        http_response_code($statusCode);
    }

    /**
     * Send Header
     *
     * Output Header
     *
     * @param  string  $key
     * @param  string|array<string>  $value
     * @return void
     */
    public function sendHeader(string $key, mixed $value): void
    {
        if (\is_array($value)) {
            foreach ($value as $v) {
                \header($key.': '.$v, false);
            }
        } else {
            \header($key.': '.$value);
        }
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
