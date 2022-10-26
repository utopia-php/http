<?php

namespace Utopia\Validator;

use Utopia\Validator;

/**
 * Wildcard
 *
 * Does not perform any validation. Always returns valid
 */
class Wildcard extends Validator
{
    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Every input is valid';
    }

    /**
     * Is valid
     *
     * Validation will always pass irrespective of input
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return true;
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
