<?php

namespace Utopia\HTTP\Adapter\Swoole;

use Swoole\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\HTTP\HTTP\App;

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
    protected SwooleServer $server;
    protected string $host;
    protected int $port;

    function __construct(string $host = '0.0.0.0', int $port = 80) {
        $this->host = $host;
        $this->port = $port;
        $this->server = new SwooleServer($host, $port);
    }

    /**
     * Starts the Server.
     * @return void 
     */
    public function start(): void
    {
        Files::load(__DIR__ . '/../public');

        $this->server->on('start', function (Server $http) {
            // $app = new App('UTC');
        });
        
        $this->server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) {
            $request = new Request($swooleRequest);
            $response = new Response($swooleResponse);
        
            if(Files::isFileLoaded($request->getURI())) {
                $time = (60 * 60 * 24 * 365 * 2); // 45 days cache
        
                return $response
                    ->setContentType(Files::getFileMimeType($request->getURI()))
                    ->addHeader('Cache-Control', 'public, max-age='.$time)
                    ->addHeader('Expires', \date('D, d M Y H:i:s', \time() + $time).' GMT') // 45 days cache
                    ->send(Files::getFileContents($request->getURI()))
                ;
            }
        
            $app = new App('UTC');

            $app
                ->run($request, $response);
        });        
    }

    /**
     * Shuts down the Server.
     * @return void 
     */
    public function end(): void
    {
        
    }
}