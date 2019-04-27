<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Validator
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
 * @version 2.0
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Validator;

use Utopia\Validator;

/**
 * ArrayList
 *
 * Validate that an variable is a valid array value and each element passes given validation
 *
 * @package Utopia\Validator
 */
class ArrayList extends Validator
{
    /**
     * @var int
     */
    protected $validator = null;

    /**
     * Text constructor.
     *
     * Get a limit param for maximum text length, when 0 length is unlimited
     *
     * @param Validator $validator
     */
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
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
        return 'Value must be an array and ' . $this->validator->getDescription();
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is valid array and validator is valid.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if(!is_array($value)) {
            return false;
        }

        foreach($value as $element) {
            if(!$this->validator->isValid($element)) {
                return false;
            }
        }

        return true;
    }
}