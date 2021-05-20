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
     * @var string
     */
    protected $format;

    /**
     * @param int $min
     * @param int $max
     * @param string $format
     */
    public function __construct($min, $max, $format = self::TYPE_INTEGER)
    {
        $this->min = $min;
        $this->max = $max;
        $this->format = $format;
    }

    /**
     * Get Range Minimum Value
     * @return int
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * Get Range Maximum Value
     * @return int
     */
    public function getMax(): int
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
     * Doesn't strictly check for the Format, this validator will attempt to cast it to the right format given
     * in the constructor.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (!parent::isValid($value)) {
            return false;
        }

        switch ($this->format) {
            case self::TYPE_INTEGER:
                $value = (int) $value;
                break;
            case self::TYPE_FLOAT:
                $value = (float) $value;
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
