<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Validator
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Validator;

use Utopia\Validator;

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
    protected $rules = [];

    /**
     * Constructor
     *
     * Multiple constructor can get any number of arguments containing Validator instances using PHP func_get_args function.
     *
     * Example:
     *
     * $multiple = new Multiple($validator1, $validator2, $validator3);
     */
    public function __construct()
    {
        // array of all method arguments
        $rules = \func_get_args();

        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
    }

    /**
     * Add rule
     *
     * Add a new rule to the end of the rules containing array
     *
     * @param Validator $rule
     * @return $this
     */
    public function addRule(Validator $rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    public function getDescription()
    {
        $description = 'Value must be:' . "\n";

        foreach ($this->rules as $key => $rule) {
            $description .= (++$key) . '. ' . $rule->getDescription() . " \n";
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
    public function isValid($value)
    {
        foreach ($this->rules as $rule) { /* @var $rule Validator */
            if (false === $rule->isValid($value)) {
                return false;
            }
        }

        return true;
    }
}
