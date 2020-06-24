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
 * Length
 *
 * Validates that an length of a string.
 *
 * @package Utopia\Validator
 */
class Length extends Validator
{
    /**
     * @var int
     */
    protected $min;

    /**
     * @var int
     */
    protected $max;

    /**
     * @param int $min
     * @param int $max
     */
    public function __construct($min, $max)
    {
        $this->min = $min;
        $this->max = $max;
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
        return 'Value must be between ' . \number_format($this->min) . ' and ' . \number_format($this->max) . ' chars';
    }

    /**
     * Is valid
     *
     * Validation will pass when $value number is bigger or equal than $min number and lower or equal than $max.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $length = \mb_strlen($value);

        if ($this->min <= $length && $this->max >= $length) {
            return true;
        }

        return false;
    }
}
