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

class RouteTest extends TestCase
{
    /**
     * @var Route
     */
    protected $route;

    public function setUp():void
    {
        $this->route = new Route('GET', '/');
    }

    public function testMethod()
    {
        $this->assertEquals('GET', $this->route->getMethod());
    }

    public function testURL()
    {
        $this->assertEquals('/', $this->route->getURL());
        
        $this->route->URL('/path');

        $this->assertEquals('/path', $this->route->getURL());
    }

    public function testDesc()
    {
        $this->assertEquals('', $this->route->getDesc());
        
        $this->route->desc('new route');

        $this->assertEquals('new route', $this->route->getDesc());
    }

    public function testGroups()
    {
        $this->assertEquals([], $this->route->getGroups());
        
        $this->route->groups(['api', 'homepage']);

        $this->assertEquals(['api', 'homepage'], $this->route->getGroups());
    }

    public function testAction()
    {
        $this->assertEquals(function(): void {}, $this->route->getAction());
        
        $this->route->action(function() {return 'hello world';});

        $this->assertEquals('hello world', $this->route->getAction()());
    }

    public function testParam()
    {
        $this->assertEquals([], $this->route->getParams());
        
        $this->route
            ->param('x', '', new Text(10))
            ->param('y', '', new Text(10))
        ;

        $this->assertCount(2, $this->route->getParams());
    }

    public function testResources()
    {
        $this->assertEquals([], $this->route->getResources());
        
        $this->route
            ->action(function() {}, ['user', 'time'])
        ;

        $this->assertCount(2, $this->route->getResources());
        $this->assertEquals('user', $this->route->getResources()[0]);
        $this->assertEquals('time', $this->route->getResources()[1]);
    }

    public function testLabel()
    {
        $this->assertEquals('default', $this->route->getLabel('key', 'default'));
        
        $this->route->label('key', 'value');

        $this->assertEquals('value', $this->route->getLabel('key', 'default'));
    }

    public function tearDown():void
    {
        $this->route = null;
    }
}