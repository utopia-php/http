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
 * Text
 *
 * Validate that an variable is a valid text value
 *
 * @package Utopia\Validator
 */
class Text extends Validator
{
    /**
     * @var int
     */
    protected $length = 0;

    /**
     * Text constructor.
     *
     * Get a limit param for maximum text length, when 0 length is unlimited
     *
     * @param int $length
     */
    public function __construct(int $length)
    {
        $this->length = $length;
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
        $message = 'Value must be a string';

        if ($this->length) {
            $message .= ' and no longer than ' . $this->length . ' chars';
        }
        return $message;
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is valid email address.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!\is_string($value)) {
            return false;
        }

        if (\mb_strlen($value) > $this->length && $this->length != 0) {
            return false;
        }

        return true;
    }
}
