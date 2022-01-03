<?php

namespace Utopia\Tests;

use Utopia\Request as UtopiaRequest;

class UtopiaRequestTest extends UtopiaRequest
{
    /**
     * @var array
     */
    private static $params = null;

    /**
     * Get Param
     *
     * Get param by current method name
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $key, $default = null): mixed
    {
        if($this::_hasParams() && \in_array($key, $this::_getParams())) {
            return $this::_getParams()[$key];
        }

        return parent::getParam($key, $default);
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
        $paramsArray = [];

        if($this::_hasParams()) {
            $paramsArray = $this::_getParams();
        }

        return \array_merge($paramsArray, parent::getParams());
    }


    /**
     * Function to set a response filter
     *
     * @param ?array $params
     *
     * @return void
     */
    public static function _setParams(?array $params)
    {
        self::$params = $params;
    }

    /**
     * Return the currently set filter
     *
     * @return ?array
     */
    public static function _getParams(): ?array
    {
        return self::$params;
    }

    /**
     * Check if a filter has been set
     *
     * @return bool
     */
    public static function _hasParams(): bool
    {
        return self::$params != null;
    }
}