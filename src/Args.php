<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia;

class Args
{
    // TODO: Add PHPDocs
    protected $args = [];

    public function __construct(array $args)
    {
        $this->args = $args;
    }

    public function get() {
        return $this->args;
    }

    public function set(array $args) {
        $this->args = $args;
    }
}