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

use Utopia\HTTP\HTTP\Response as HTTPResponse;

class Response extends HTTPResponse
{
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
}
