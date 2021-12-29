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
 * ArrayList
 *
 * Validate that an variable is a valid array value and each element passes given validation
 *
 * @package Utopia\Validator
 */
class ArrayList extends Validator
{
    /**
     * @var Validator
     */
    protected Validator $validator;

    /**
     * @var int
     */
    protected int $length;

    /**
     * Array constructor.
     *
     * Pass a validator that must be applied to each element in this array
     *
     * @param Validator $validator
     * @param int $length
     */
    public function __construct(Validator $validator, int $length = 0)
    {
        $this->validator = $validator;
        $this->length = $length;
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
        return 'Value must a valid array and ' . $this->validator->getDescription();
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
        return true;
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
        return $this->validator->getType();
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is valid array and validator is valid.
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if (!\is_array($value)) {
            return false;
        }

        if ($this->length && \count($value) > $this->length) {
            return false;
        }

        foreach ($value as $element) {
            if (!$this->validator->isValid($element)) {
                return false;
            }
        }

        return true;
    }
}
