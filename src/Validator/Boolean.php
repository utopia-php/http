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
 * Bool
 *
 * Validate that an variable is a boolean value
 *
 * @package Utopia\Validator
 */
class Boolean extends Validator
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
        return 'Value must be a boolean';
    }

    /**
     * Is valid
     *
     * Validation will pass when $value has a boolean value.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_bool($value)) {
            return false;
        }

        return true;
    }
}