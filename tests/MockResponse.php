<?php

namespace Utopia\Http\Tests;

use Utopia\Http\Adapter\FPM\Response as UtopiaFPMResponse;

class MockResponse extends UtopiaFPMResponse
{
    /**
     * Send Status Code
     *
     * @param  int  $statusCode
     * @param  string  $reason
     * @return void
     */
    protected function sendStatus(int $statusCode, string $reason): void
    {
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
    }
}
