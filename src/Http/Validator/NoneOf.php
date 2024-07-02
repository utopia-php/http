<?php

namespace Utopia\Http\Validator;

use Utopia\Http\Validator;

/**
 * Ensure no validators from a list passed the check
 *
 * @package Utopia\Validator
 */
class NoneOf extends Validator
{
    /**
     * @param array<Validator> $validators
     */
    public function __construct(protected array $validators, protected string $type = self::TYPE_MIXED)
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
        $description = '';
        foreach ($this->validators as $key => $rule) {
            $description .= ++$key . '. ' . $rule->getDescription() . " \n";
        }

        return $description;
    }

    /**
     * Is valid
     *
     * Validation will pass when all rules are valid if only one of the rules is invalid validation will fail.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        foreach ($this->validators as $rule) {
            $valid = $rule->isValid($value);

            if($valid) {
                return false;
            }
        }

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
        return $this->type;
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
}
