<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Utopia\Http\Http;
use Utopia\Http\Response;
use Utopia\Http\Adapter\Swoole\Request;
use Utopia\Http\Adapter\Swoole\Response as SwooleResponse;
use Utopia\Http\Adapter\Swoole\Server;

ini_set('memory_limit', '1024M');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/routes.php';

// ── Swoole-only: non-detach streaming endpoints ──

Http::get('/stream/non-detach/generator')
    ->inject('response')
    ->action(function (Response $response) {
        /** @var SwooleResponse $response */
        $response->setDetach(false);

        $chunks = ['nd-chunk1-', 'nd-chunk2-', 'nd-chunk3'];
        $totalSize = array_sum(array_map('strlen', $chunks));

        $generator = (function () use ($chunks) {
            foreach ($chunks as $chunk) {
                yield $chunk;
            }
        })();

        $response->stream($generator, $totalSize);
    });

Http::get('/stream/non-detach/callable')
    ->inject('response')
    ->action(function (Response $response) {
        /** @var SwooleResponse $response */
        $response->setDetach(false);

        $data = str_repeat('B', 1000);

        $response->stream(function (int $offset, int $length) use ($data) {
            return substr($data, $offset, $length);
        }, strlen($data));
    });

$server = new Server('0.0.0.0', '80');
$app = new Http('UTC');
$app->setCompression(true);
$server->onStart(function () {
    echo 'Swoole server started on port 80' . PHP_EOL;
});
$server->onRequest(function (Request $request, SwooleResponse $response) use ($app) {
    $app->run($request, $response);
});
$server->start();
