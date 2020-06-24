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

class HexColor extends Validator
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Value must be a valid Hex color code';
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (\is_string($value) && \preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
            return true;
        }

        return false;
    }
}
