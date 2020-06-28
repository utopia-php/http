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
use Utopia\Validator\Text;

class AppTest extends TestCase
{
    /**
     * @var App
     */
    protected $app = null;

    public function setUp()
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
        App::setResource('rand', function () {return rand();});

        $resource = $this->app->getResource('rand');

        $this->assertNotEmpty($resource);
        $this->assertEquals($resource, $this->app->getResource('rand'));
        $this->assertEquals($resource, $this->app->getResource('rand'));
        $this->assertEquals($resource, $this->app->getResource('rand'));

        // Default Params
        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->inject('rand')
            ->action(function($x, $y, $rand) {
                echo $x.'-'.$y.'-'.$rand;
            })
        ;

        \ob_start();
        $this->app->execute($route, []);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def-'.$resource, $result);
    }

    public function testExecute()
    {
        $this->app->error(function() {
            echo 'error';
        });

        // Default Params
        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function($x, $y) {
                echo $x.'-'.$y;
            })
        ;

        \ob_start();
        $this->app->execute($route, []);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def', $result);

        // With Params

        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function($x, $y) {
                echo $x.'-',$y;
            })
        ;

        \ob_start();
        $this->app->execute($route, ['x' => 'param-x', 'y' => 'param-y']);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('param-x-param-y', $result);

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
        $this->app->execute($route, ['x' => 'param-x', 'y' => 'param-y']);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error', $result);

        // With Hooks

        $this->app->init(function() {
            echo 'init-';
        });
        
        $this->app->shutdown(function() {
            echo '-shutdown';
        });

        $this->app->init(function() {
            echo '(init-api)-';
        }, 'api');

        $this->app->shutdown(function() {
            echo '-(shutdown-api)';
        }, 'api');

        $this->app->init(function() {
            echo '(init-homepage)-';
        }, 'homepage');

        $this->app->shutdown(function() {
            echo '-(shutdown-homepage)';
        }, 'homepage');

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
        $this->app->execute($route, ['x' => 'param-x', 'y' => 'param-y']);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-(init-api)-param-x-param-y-(shutdown-api)-shutdown', $result);

        \ob_start();
        $this->app->execute($homepage, ['x' => 'param-x', 'y' => 'param-y']);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-(init-homepage)-param-x*param-y-(shutdown-homepage)-shutdown', $result);
    }

    public function tearDown()
    {
        $this->app = null;
    }
}