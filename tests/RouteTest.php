<?php

namespace Utopia;

use PHPUnit\Framework\TestCase;
use Utopia\Validator\Text;

class RouteTest extends TestCase
{
    protected ?Route $route;

    public function setUp(): void
    {
        $this->route = new Route('GET', '/');
    }

    public function testCanGetMethod()
    {
        $this->assertEquals('GET', $this->route->getMethod());
    }

    public function testCanGetAndSetPath()
    {
        $this->assertEquals('/', $this->route->getPath());

        $this->route->path('/path');

        $this->assertEquals('/path', $this->route->getPath());
    }

    public function testCanSetAndGetDescription()
    {
        $this->assertEquals('', $this->route->getDesc());

        $this->route->desc('new route');

        $this->assertEquals('new route', $this->route->getDesc());
    }

    public function testCanSetAndGetGroups()
    {
        $this->assertEquals([], $this->route->getGroups());

        $this->route->groups(['api', 'homepage']);

        $this->assertEquals(['api', 'homepage'], $this->route->getGroups());
    }

    public function testCanSetAndGetAction()
    {
        $this->assertEquals(function (): void {
        }, $this->route->getAction());

        $this->route->action(fn () => 'hello world');

        $this->assertEquals('hello world', $this->route->getAction()());
    }

    public function testCanGetAndSetParam()
    {
        $this->assertEquals([], $this->route->getParams());

        $this->route
            ->param('x', '', new Text(10))
            ->param('y', '', new Text(10));

        $this->assertCount(2, $this->route->getParams());
    }

    public function testCanInjectResources()
    {
        $this->assertEquals([], $this->route->getInjections());

        $this->route
            ->inject('user')
            ->inject('time')
            ->action(function () {
            });

        $this->assertCount(2, $this->route->getInjections());
        $this->assertEquals('user', $this->route->getInjections()['user']['name']);
        $this->assertEquals('time', $this->route->getInjections()['time']['name']);
    }

    public function testCanSetAndGetLabels()
    {
        $this->assertEquals('default', $this->route->getLabel('key', 'default'));

        $this->route->label('key', 'value');

        $this->assertEquals('value', $this->route->getLabel('key', 'default'));
    }

    public function testCanSetAndGetHooks()
    {
        $this->assertTrue($this->route->getHook());
        $this->route->hook(true);
        $this->assertTrue($this->route->getHook());
        $this->route->hook(false);
        $this->assertFalse($this->route->getHook());
    }

    public function tearDown(): void
    {
        $this->route = null;
    }
}
