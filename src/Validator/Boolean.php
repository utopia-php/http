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
     * @var bool
     */
    protected $strings = false;

    /**
     * Pass true to accept true and false strings as valid booleans
     * This option is good for validating query string params.
     * 
     * @param bool $strings
     */
    public function __construct(bool $strings = false)
    {
        $this->strings = $strings;
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
        if($this->strings && ($value === 'true' || $value === 'false')) {
            return true;
        }

        if (\is_bool($value)) {
            return true;
        }

        return false;
    }
}