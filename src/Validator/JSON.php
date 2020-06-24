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

class JSON extends Validator
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Value must be a valid JSON string';
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (\is_array($value)) {
            return true;
        }

        if (\is_string($value)) {
            \json_decode($value);
            return (\json_last_error() == JSON_ERROR_NONE);
        }
        
        return false;
    }
}
