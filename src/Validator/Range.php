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
     * @var int|float
     */
    protected int|float $min;

    /**
     * @var int|float
     */
    protected int|float $max;

    /**
     * @var string
     */
    protected string $format;

    /**
     * @param int|float $min
     * @param int|float $max
     * @param string $format
     */
    public function __construct(int|float $min, int|float $max, string $format = self::TYPE_INTEGER)
    {
        $this->min = $min;
        $this->max = $max;
        $this->format = $format;
}
    /**
     * Get Range Minimum Value
     * @return int|float
     */
    public function getMin(): int|float
    {
        return $this->min;
    }

    /**
     * Get Range Maximum Value
     * @return int|float
     */
    public function getMax(): int|float
    {
        return $this->max;
    }

    /**
     * Get Range Format
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Value must be a valid range between ' . \number_format($this->min) . ' and ' . \number_format($this->max);
    }

    /**
     * Is array
     *
     * Function will return true if object is array.
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return false;
    }

    /**
     * Get Type
     *
     * Returns validator type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->format;
    }

    /**
     * Is valid
     *
     * Validation will pass when $value number is bigger or equal than $min number and lower or equal than $max.
     * Not strict, considers any valid integer to be a valid float
     * Considers infinity to be a valid integer
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if (!parent::isValid($value)) {
            return false;
        }

        switch ($this->format) {
            case self::TYPE_INTEGER:
                // Accept infinity as an integer
                // Since gettype(INF) === TYPE_FLOAT
                if ($value === INF || $value === -INF) {
                    break; // move to check if value is within range
                }
                $value = $value + 0;
                if(!is_int($value)) {
                    return false;
                }
                break;
            case self::TYPE_FLOAT:
                $value = $value + 0;
                if(!is_float($value) && !is_int($value)) {
                    return false;
                }
                break;
            default:
                return false;
        }

        if ($this->min <= $value && $this->max >= $value) {
            return true;
        }

        return false;
    }
}
