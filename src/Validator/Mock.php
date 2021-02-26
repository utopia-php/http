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
 * Null
 *
 * Validate that always validate data as valid
 *
 * @package Utopia\Validator
 */
class Mock extends Validator
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
        return 'Every input is valid';
    }

    /**
     * Get Type 
     *
     * Returns validator type 
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE_MIXED; 
    }

    /**
     * Is valid
     *
     * Validation will pass with any input. 
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        return true;
    }
}
