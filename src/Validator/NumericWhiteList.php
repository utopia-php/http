<?php

namespace Utopia\Validator;

use Utopia\Validator;

/**
 * Numeric WhiteList
 *
 * Checks if a variable is inside predefined numerical white list.
 */
class NumericWhiteList extends Validator
{
    /**
     * @var int[]
     */
    protected array $list;

    /**
     * @var bool
     */
    protected bool $strict;

    /**
     * Constructor
     *
     * Sets a white list array.
     *
     * @param  int[]  $list
     * @param  bool  $strict disable type check
     */
    public function __construct(array $list, bool $strict = flase)
    {
        $this->list = $list;
        $this->strict = $strict;
    }

    /**
     * Get List of All Allowed Values
     *
     * @return array
     */
    public function getList(): array
    {
        return $this->list;
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
        return 'Value must be one of ('.\implode(', ', $this->list).')';
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
        return self::TYPE_INTEGER;
    }

    /**
     * Is valid
     *
     * Validation will pass if $value is in the white list array.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if (\is_array($value)) {
            return false;
        }

        if (!\in_array($value, $this->list, $this->strict)) {
            return false;
        }

        return true;
    }
}
