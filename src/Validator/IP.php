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
 * IP
 *
 * Validate that an variable is a valid IP address
 *
 * @package Utopia\Validator
 */
class IP extends Validator
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
        return 'Value must be a valid IP address';
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is valid IP address.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!\filter_var($value, FILTER_VALIDATE_IP)) {
            return false;
        }

        return true;
    }
}