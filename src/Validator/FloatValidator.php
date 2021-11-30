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
     * @var bool
     */
    protected bool $loose = false;

    /**
     * Pass true to accept float strings as valid float values
     * This option is good for validating query string params.
     *
     * @param bool $loose
     */
    public function __construct(bool $loose = false)
    {
        $this->loose = $loose;
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
        return 'Value must be a valid float';
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
        return self::TYPE_FLOAT;
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is float.
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if($this->loose) {
            if(!\is_numeric($value)) {
                return false;
            }
            $value = $value+0;
        }
        if (!\is_float($value) && !\is_int($value)) {
            return false;
        }

        return true;
    }
}
