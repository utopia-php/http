<?php
/**
 * Utopia HTTP
 *
 * @package HTTP
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/http
 * @author Appwrite Team <team@appwrite.io>
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\HTTP;

abstract class Validator
{

    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'double'; /* gettype() returns 'double' for historical reasons */
    const TYPE_STRING = 'string';
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';
    const TYPE_MIXED = 'mixed';

    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * Is array 
     *
     * Returns true if an array or false if not.
     *
     * @return bool
     */
    abstract public function isArray(): bool;

    /**
     * Is valid
     *
     * Returns true if valid or false if not.
     *
     * @param  mixed $value
     * @return bool
     */
    abstract public function isValid($value): bool;

    /**
     * Get Type
     *
     * Returns validator type.
     *
     * @return string
     */
    abstract public function getType(): string;
}
