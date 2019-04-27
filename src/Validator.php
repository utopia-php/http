<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/eldadfux/Utopia-PHP-Framework
 * @author Eldad Fux <eldad@fuxie.co.il>
 * @version 2.0
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia;

abstract class Validator
{
    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    abstract public function getDescription();

    /**
     * Is valid
     *
     * Returns true if valid or false if not.
     *
     * @param  mixed $value
     * @return bool
     */
    abstract public function isValid($value);
}
