<?php

declare(strict_types=1);

namespace Utopia\Http\Adapter\FPM;

use Utopia\Http\Response as UtopiaResponse;

class Response extends UtopiaResponse
{
    /**
     * Write
     *
     * Send output
     *
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
     */
    public function end(?string $content = null): void
    {
        if (!is_null($content)) {
            echo $content;
        }
    }


    /**
     * Send Status Code
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
     * @param  string|array<string>  $value
     */
    public function sendHeader(string $key, mixed $value): void
    {
        if (\is_array($value)) {
            foreach ($value as $v) {
                \header($key . ': ' . $v, false);
            }
        } else {
            \header($key . ': ' . $value);
        }
    }

    /**
     * Send Cookie
     *
     * Output Cookie
     *
     * @param  array<string, mixed>  $options
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
