<?php

namespace Utopia\Http\Validator;

use Utopia\Http\Validator;

/**
 * Host
 *
 * Validate that a host is allowed from given whitelisted hosts list
 *
 * @package Utopia\Http\Validator
 */
class Host extends Validator
{
    protected $whitelist = [];

    /**
     * @param array $whitelist
     */
    public function __construct(array $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'URL host must be one of: ' . \implode(', ', $this->whitelist);
    }

    /**
     * Is valid
     *
     * Validation will pass when $value starts with one of the given hosts
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $urlValidator = new URL();

        if (!$urlValidator->isValid($value)) {
            return false;
        }

        $hostnameValidator = new Hostname($this->whitelist);

        return $hostnameValidator->isValid(\parse_url($value, PHP_URL_HOST));
    }

    /**
     * Is array
     *
     * Function will return true if object is array.
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return false;
    }

    /**
     * Get Type
     *
     * Returns validator type.
     *
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_STRING;
    }
}
