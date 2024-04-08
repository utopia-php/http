<?php

namespace Utopia\Http\Tests;

use Utopia\Http\Adapter\FPM\Request as UtopiaFPMRequest;

class MockRequest extends UtopiaFPMRequest
{
    protected array $overrides = [];

    /**
     * Constructor
     */
    public function __construct(array $overrides = [])
    {
        $this->overrides = $overrides;
    }

    /**
     * Get Param
     *
     * Get param by current method name
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam(string $key, $default = null): mixed
    {
        $params = \array_merge($this->overrides, parent::getParams());

        if (\array_key_exists($key, $params)) {
            return $params[$key];
        }
        
        return $default;
    }

    /**
     * Get Params
     *
     * Get all params of current method
     *
     * @return array
     */
    public function getParams(): array
    {
        $params = \array_merge(parent::getParams(), $this->overrides);

        return $params;
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
    public function sendHeader(string $key, string $value): void
    {
    }
}
