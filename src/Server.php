<?php
namespace Utopia\HTTP;

use Throwable;
use Utopia\HTTP\Adapter;

/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 */
class Server
{
    protected Adapter $adapter;

    /**
     * Creates an instance of a HTTP server.
     * @param Adapter $adapter 
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Start the HTTP server
     * 
     * @return void 
     */
    public function start(): void
    {
    }

    /**
     * Shuts down the WebSocket server.
     * @return void 
     */
    public function end(): void
    {
        
    }
}