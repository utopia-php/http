<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Validator
 *
 * @link https://github.com/eldadfux/Utopia-PHP-Framework
 * @author Eldad Fux <eldad@fuxie.co.il>
 * @version 2.0
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Validator;

use Utopia\Validator;

/**
 * Host
 *
 * Validate that a host is allowed from given whitelisted hosts list
 *
 * @package Utopia\Validator
 */
class Host extends Validator
{
    protected $whitelist = [];

    /**
     * @param array $whitelist
     */
    public function __construct(array $whitelist)
    {
        $this->whitelist = $whitelist;
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
        return 'URL host must be one of: ' . implode(', ', $this->whitelist);
    }

    /**
     * Is valid
     *
     * Validation will pass when $value starts with one of the given hosts
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $urlValidator = new URL();

        if(!$urlValidator->isValid($value)) {
            return false;
        }

        foreach ($this->whitelist as $host) {
            if(substr($value, 0, strlen($host)) === $host) {
                return true;
            }
        }

        return false;
    }
}