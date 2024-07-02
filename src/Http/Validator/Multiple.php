<?php

namespace Utopia\Http\Validator;

use Utopia\Http\Validator;

/**
 * Multiple
 *
 * Multiple validator is a container of multiple validations each acting as a rule.
 *
 * @package Utopia\Validator
 */
class Multiple extends Validator
{
    /**
     * @var Validator[]
     */
    protected $validators = [];

    protected $type = self::TYPE_MIXED;
    protected $rule = self::RULE_ALL;

    public const RULE_ALL = "ruleAll";
    public const RULE_ANY = "ruleAny";
    public const RULE_NONE = "ruleNone";

    /**
     * Constructor
     *
     * Multiple constructor can get any number of arguments containing Validator instances using PHP func_get_args function.
     *
     * Example:
     *
     * $multiple = new Multiple([$validator1, $validator2]);
     * $multiple = new Multiple([$validator1, $validator2, $validator3], self::TYPE_STRING);
     *
     * Rule is set to define criteria of validation:
     * RULE_ANY: At least one validator must pass
     * RULE_ALL: All validators must pass
     * RULE_NONE: No validators must pass - all validators must fail
     */
    public function __construct(array $validators, ?string $type = self::TYPE_MIXED, ?string $rule = self::RULE_ALL)
    {
        foreach ($validators as $validator) {
            $this->addValidator($validator);
        }

        $this->type = $type;
        $this->rule = $rule;
    }
    /**
     * Add validator
     *
     * Add a new validator to check against during isVaid() call
     *
     * @param Validator $validator
     * @return $this
     */
    public function addValidator(Validator $validator)
    {
        $this->validators[] = $validator;

        return $this;
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
        foreach ($this->validators as $rule) { /* @var $rule Validator */
            $valid = $rule->isValid($value);

            // Oprimization improvements
            if($this->rule === self::RULE_ALL) {
                if(!$valid) {
                    return false;
                }
            } if($this->rule === self::RULE_NONE) {
                if($valid) {
                    return false;
                }
            } if($this->rule === self::RULE_ANY) {
                if($valid) {
                    return true;
                }
            }
        }

        if($this->rule === self::RULE_ANY) {
            return false;
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
