<?php

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Utopia\Validator\Text;

class RouteTest extends TestCase
{
    protected ?Route $route;

    public function setUp(): void
    {
        $this->route = new Route('GET', '/');
    }

    public function testCanGetMethod(): void
    {
        $this->assertEquals('GET', $this->route->getMethod());
    }

    public function testCanGetAndSetPath(): void
    {
        $this->assertEquals('/', $this->route->getPath());

        $this->route->path('/path');

        $this->assertEquals('/path', $this->route->getPath());
    }

    public function testCanSetAndGetDescription(): void
    {
        $this->assertEquals('', $this->route->getDesc());

        $this->route->desc('new route');

        $this->assertEquals('new route', $this->route->getDesc());
    }

    public function testCanSetAndGetGroups(): void
    {
        $this->assertEquals([], $this->route->getGroups());

        $this->route->groups(['api', 'homepage']);

        $this->assertEquals(['api', 'homepage'], $this->route->getGroups());
    }

    public function testCanSetAndGetAction(): void
    {
        $this->assertEquals(function (): void {}, $this->route->getAction());

        $this->route->action(fn() => 'hello world');

        $this->assertEquals('hello world', $this->route->getAction()());
    }

    public function testCanGetAndSetParam(): void
    {
        $this->assertEquals([], $this->route->getParams());

        $this->route
            ->param('x', '', new Text(10))
            ->param('y', '', new Text(10));

        $this->assertCount(2, $this->route->getParams());
    }

    public function testCanInjectResources(): void
    {
        $this->assertEquals([], $this->route->getInjections());

        $this->route
            ->inject('user')
            ->inject('time')
            ->action(function () {});

        $this->assertCount(2, $this->route->getInjections());
        $this->assertEquals('user', $this->route->getInjections()['user']['name']);
        $this->assertEquals('time', $this->route->getInjections()['time']['name']);
    }

    public function testCanSetAndGetLabels(): void
    {
        $this->assertEquals('default', $this->route->getLabel('key', 'default'));

        $this->route->label('key', 'value');

        $this->assertEquals('value', $this->route->getLabel('key', 'default'));
    }

    public function testCanSetAndGetHooks(): void
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
