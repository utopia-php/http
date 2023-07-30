<?php

namespace Utopia\Validator;

use Utopia\Validator;

class Nullable extends Validator
{
    public function __construct(protected Validator $validator)
    {
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
        return $this->validator->getDescription() . ' or null';
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
        return $this->validator->getType();
    }

    /**
     * @return Validator
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is text with valid length.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if (\is_null($value)) {
            return true;
        }

        return $this->validator->isValid($value);
    }
}
