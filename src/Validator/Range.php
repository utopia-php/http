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

/**
 * Range
 *
 * Validates that an number is in range.
 *
 * @package Utopia\Validator
 */
class Range extends Numeric
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
     * Get Range Minimum Value
     * @return int
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Get Range Maximum Value
     * @return int
     */
    public function getMax()
    {
        return $this->max;
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
        return 'Value must be in range between ' . \number_format($this->min) . ' and ' . \number_format($this->max);
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
        if (!parent::isValid($value)) {
            return false;
        }

        if ($this->min <= $value && $this->max >= $value) {
            return true;
        }

        return false;
    }
}
