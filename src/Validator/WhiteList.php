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
    protected array $list;

    /**
     * @var bool
     */
    protected bool $strict;

    /**
     * @var string
     */
    protected string $type;

    /**
     * Constructor
     *
     * Sets a white list array and strict mode.
     *
     * @param array $list
     * @param bool  $strict disable type check and be case insensetive
     * @param string $type of $list items
     */
    public function __construct(array $list, bool $strict = false, string $type = self::TYPE_STRING)
    {
        $this->list     = $list;
        $this->strict   = $strict;
        $this->type     = $type;

        if (!$this->strict) {
            foreach ($this->list as $key => &$value) {
                $this->list[$key] = \strtolower($value);
            }
        }
    }

    /**
     * Get List of All Allowed Values
     *
     * @return array
     */
    public function getList(): array
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
    public function getDescription(): string
    {
        return 'Value must be one of (' . \implode(', ', $this->list) . ')';
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
        return $this->type;
    }

    /**
     * Is valid
     *
     * Validation will pass if $value is in the white list array.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if (\is_array($value)) {
            return false;
        }

        $value = ($this->strict) ? $value : \strtolower($value);

        if (!\in_array($value, $this->list, $this->strict)) {
            return false;
        }

        return true;
    }
}
