<?php

namespace Utopia\Http\Validator;

use Utopia\Http\Validator;

/**
 * ArrayList
 *
 * Validate that an variable is a valid array value and each element passes given validation
 */
class ArrayList extends Validator
{
    /**
     * @var Validator
     */
    protected Validator $validator;

    /**
     * @var int
     */
    protected int $length;

    /**
     * Array constructor.
     *
     * Pass a validator that must be applied to each element in this array
     *
     * @param  Validator  $validator
     * @param  int  $length
     */
    public function __construct(Validator $validator, int $length = 0)
    {
        $this->validator = $validator;
        $this->length = $length;
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
        $msg = 'Value must a valid array';

        if ($this->length > 0) {
            $msg .= ' no longer than ' . $this->length . ' items';
        }

        if (!empty($this->validator->getDescription())) {
            $msg .= ' and ' . $this->validator->getDescription();
        }

        return $msg;
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
        return true;
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
        return $this->validator->getType();
    }

    /**
     * Get Nested Validator
     *
     * @return Validator
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is valid array and validator is valid.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if (!\is_array($value)) {
            return false;
        }

        foreach ($value as $element) {
            if (!$this->validator->isValid($element)) {
                return false;
            }
        }

        if ($this->length && \count($value) > $this->length) {
            return false;
        }

        return true;
    }
}
