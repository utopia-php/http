<?php
/**
 * Utopia HTTP
 *
 * @package HTTP
 * @subpackage Validator
 *
 * @link https://github.com/utopia-php/http
 * @author Appwrite Team <team@appwrite.io>
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\HTTP\Validator;

use Utopia\HTTP\Validator;

class HexColor extends Validator
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Value must be a valid Hex color code';
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
        return self::TYPE_STRING;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if (\is_string($value) && \preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
            return true;
        }

        return false;
    }
}
