<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia;

use PHPUnit\Framework\TestCase;
use Utopia\Tests\UtopiaRequestTest;
use Utopia\Validator\Text;

App::setResource('rand', function () {return rand();});
App::setResource('first', function ($second) {return 'first-'.$second;}, ['second']);
App::setResource('second', function () {return 'second';});

class AppTest extends TestCase
{
    /**
     * @var App
     */
    protected $app = null;

    public function setUp():void
    {
        $this->app = new App('Asia/Tel_Aviv');
    }

    public function testIsMode()
    {

        $this->assertEquals(null, App::getMode());
        $this->assertEquals(false, App::isProduction());
        $this->assertEquals(false, App::isDevelopment());
        $this->assertEquals(false, App::isStage());

        App::setMode(App::MODE_TYPE_PRODUCTION);

        $this->assertEquals(App::MODE_TYPE_PRODUCTION, App::getMode());
        $this->assertEquals(true, App::isProduction());
        $this->assertEquals(false, App::isDevelopment());
        $this->assertEquals(false, App::isStage());

        App::setMode(App::MODE_TYPE_DEVELOPMENT);

        $this->assertEquals(App::MODE_TYPE_DEVELOPMENT, App::getMode());
        $this->assertEquals(false, App::isProduction());
        $this->assertEquals(true, App::isDevelopment());
        $this->assertEquals(false, App::isStage());

        App::setMode(App::MODE_TYPE_STAGE);

        $this->assertEquals(App::MODE_TYPE_STAGE, App::getMode());
        $this->assertEquals(false, App::isProduction());
        $this->assertEquals(false, App::isDevelopment());
        $this->assertEquals(true, App::isStage());
    }

    public function testGetEnv()
    {
        // Mock
        $_SERVER['key'] = 'value';

        $this->assertEquals(App::getEnv('key'), 'value');
        $this->assertEquals(App::getEnv('unknown', 'test'), 'test');
    }

    public function testResources()
    {
        $second = $this->app->getResource('second');
        $first = $this->app->getResource('first');
        $this->assertEquals('second', $second);
        $this->assertEquals('first-second', $first);

        $resource = $this->app->getResource('rand');

        $this->assertNotEmpty($resource);
        $this->assertEquals($resource, $this->app->getResource('rand'));
        $this->assertEquals($resource, $this->app->getResource('rand'));
        $this->assertEquals($resource, $this->app->getResource('rand'));

        // Default Params
        $route = new Route('GET', '/path');

        $route
            ->inject('rand')
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function($x, $y, $rand) {
                echo $x.'-'.$y.'-'.$rand;
            })
        ;

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def-'.$resource, $result);
    }

    public function testExecute()
    {
        $resource = $this->app->getResource('rand');

        $this->app->error(function($error) {
            echo 'error: '.$error->getMessage();
        }, ['error']);

        // Default Params
        $route = new Route('GET', '/path');

        $route
            ->alias('/path1',['x' => 'x-def-1', 'y' => 'y-def-1'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function($x, $y) {
                echo $x.'-'.$y;
            })
        ;

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();
        
        // test alias with param override
        $route->setIsAlias(true);
        
        \ob_start();
        $this->app->execute($route, new Request());
        $result1 = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def', $result);
        $this->assertEquals('x-def-1-y-def-1', $result1);

        // With Params

        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->inject('rand')
            ->param('z', 'z-def', function($rand) { echo $rand.'-'; return new Text(200); }, 'z param', false, ['rand'])
            ->action(function($x, $y, $z, $rand) {
                echo $x.'-',$y;
            })
        ;

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($route, $request);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals($resource.'-param-x-param-y', $result);

        // With Error

        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(1), 'x param', false)
            ->param('y', 'y-def', new Text(1), 'y param', false)
            ->action(function($x, $y) {
                echo $x.'-',$y;
            })
        ;

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($route, $request);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error: Invalid x: Value must be a valid string and no longer than 1 chars', $result);

        // With Hooks

        $this->app->init(function($rand) {
            echo 'init-'.$rand.'-';
        }, ['rand']);

        $this->app->shutdown(function() {
            echo '-shutdown';
        });

        $this->app->init(function() {
            echo '(init-api)-';
        }, [], 'api');

        $this->app->shutdown(function() {
            echo '-(shutdown-api)';
        }, [], 'api');

        $this->app->init(function() {
            echo '(init-homepage)-';
        }, [], 'homepage');

        $this->app->shutdown(function() {
            echo '-(shutdown-homepage)';
        }, [], 'homepage');

        $route = new Route('GET', '/path');

        $route
            ->groups(['api'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function($x, $y) {
                echo $x.'-',$y;
            })
        ;

        $homepage = new Route('GET', '/path');

        $homepage
            ->groups(['homepage'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function($x, $y) {
                echo $x.'*',$y;
            })
        ;

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($route, $request);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-'.$resource.'-(init-api)-param-x-param-y-(shutdown-api)-shutdown', $result);

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($homepage, $request);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-'.$resource.'-(init-homepage)-param-x*param-y-(shutdown-homepage)-shutdown', $result);
    }

    public function testMiddleWare() {
        App::reset();

        $this->app->init(function() {
            echo '(init)-';    
        });

        $this->app->shutdown(function() {
            echo '-(shutdown)';    
        });

        // Default Params
        $route = new Route('GET', '/path');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->action(function($x) {
                echo $x;
            })
        ;

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();

        // var_dump($result);
        $this->assertEquals('(init)-x-def-(shutdown)', $result);

        // Default Params
        $route = new Route('GET', '/path');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->middleware(false)
            ->action(function($x) {
                echo $x;
            })
        ;

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();

        // var_dump($result);
        $this->assertEquals('x-def', $result);
    }

    public function testSetRoute() {
        App::reset();

        $route = new Route('GET', '/path');

        $this->assertEquals($this->app->getRoute(), null);
        $this->app->setRoute($route);
        $this->assertEquals($this->app->getRoute(), $route);
    }

    public function testRun()
    {
        // Test head requests

        $method = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : null;
        $uri = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null;

        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $_SERVER['REQUEST_URI'] = '/path';

        App::get('/path')
            ->inject('response')
            ->action(function($response) {
                $response->send('HELLO');
            })
        ;

        \ob_start();
        $this->app->run(new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        $this->assertStringNotContainsString('HELLO', $result);
    }

    public function testRunAlias()
    {
        // Test head requests

        $method = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : null;
        $uri = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null;

        App::get('/storage/buckets/:bucketId/files/:fileId')
            ->alias('/storage/files/:fileId',[
                "bucketId" => "default",
            ])
            ->param('bucketId','bucketid', new Text(100), 'My id', false)
            ->param('fileId','fileId', new Text(100), 'My id', false)
            ->inject('response')
            ->action(function($bucketId, $fileId, $response) {
                $response->send("HELLO");
            })
        ;

        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $_SERVER['REQUEST_URI'] = '/storage/files/myfileid';

        // Test Alias
        \ob_start();
        $this->app->run(new Request(), new Response());
        $result1 = \ob_get_contents();
        \ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        $this->assertStringNotContainsString('HELLO', $result1);
    }

    public function tearDown():void
    {
        $this->app = null;
    }
}