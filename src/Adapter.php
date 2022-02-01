<?php

namespace Utopia\HTTP;

use Swoole\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

/**
 * Utopia HTTP
 *
 * @package HTTP
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/http
 * @author Appwrite Team <team@appwrite.io>
 */
abstract class Adapter
{
    protected string $host;
    protected int $port;

    function __construct(string $host = '0.0.0.0', int $port = 80) {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Starts the Server.
     * @return void 
     */
    public abstract function start(): void;

    /**
     * Shuts down the Server.
     * @return void 
     */
    public abstract function end(): void;
}