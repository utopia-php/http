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
 * Integer
 *
 * Validate that an variable is an integer
 *
 * @package Utopia\Validator
 */
class Integer extends Validator
{
    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Value must be a valid integer';
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is integer.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!\is_int($value)) {
            return false;
        }

        return true;
    }
}
