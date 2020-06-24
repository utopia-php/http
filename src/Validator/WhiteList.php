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
 * WhiteList
 *
 * Checks if a variable is inside predefined white list.
 *
 * @package Utopia\Validator
 */
class WhiteList extends Validator
{
    /**
     * @var array
     */
    protected $list;

    /**
     * @var bool
     */
    protected $strict;

    /**
     * Constructor
     *
     * Sets a white list array and strict mode.
     *
     * @param array $list
     * @param bool  $strict
     */
    public function __construct(array $list, $strict = false)
    {
        $this->list 	= $list;
        $this->strict 	= $strict;
    }

    /**
     * Get List of All Allowed Values
     */
    public function getList()
    {
        return $this->list;
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
        return 'Value must be one of (' . \implode(', ', $this->list) . ')';
    }

    /**
     * Is valid
     *
     * Validation will pass if $value is in the white list array.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!\in_array($value, $this->list, $this->strict)) {
            return false;
        }

        return true;
    }
}