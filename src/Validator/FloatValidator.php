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
 * Float
 *
 * Validate that an variable is a float
 *
 * @package Utopia\Validator
 */
class FloatValidator extends Validator
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
        return 'Value must be a valid float';
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is float.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!\is_float($value)) {
            return false;
        }

        return true;
    }
}
