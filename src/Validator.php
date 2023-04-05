<?php

namespace Utopia;

abstract class Validator
{
    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_FLOAT = 'double'; /* gettype() returns 'double' for historical reasons */

    public const TYPE_STRING = 'string';

    public const TYPE_ARRAY = 'array';

    public const TYPE_OBJECT = 'object';

    public const TYPE_MIXED = 'mixed';

    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * Is array
     *
     * Returns true if an array or false if not.
     *
     * @return bool
     */
    abstract public function isArray(): bool;

    /**
     * Is valid
     *
     * Returns true if valid or false if not.
     *
     * @param  mixed  $value
     * @return bool
     */
    abstract public function isValid($value): bool;

    /**
     * Get Type
     *
     * Returns validator type.
     *
     * @return string
     */
    abstract public function getType(): string;
}
