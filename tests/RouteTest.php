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

    public function testCanGetMethod()
    {
        $this->assertSame('GET', $this->route->getMethod());
    }

    public function testCanGetAndSetPath()
    {
        $this->assertSame('/', $this->route->getPath());

        $this->route->path('/path');

        $this->assertSame('/path', $this->route->getPath());
    }

    public function testCanSetAndGetDescription()
    {
        $this->assertSame('', $this->route->getDesc());

        $this->route->desc('new route');

        $this->assertSame('new route', $this->route->getDesc());
    }

    public function testCanSetAndGetGroups()
    {
        $this->assertSame([], $this->route->getGroups());

        $this->route->groups(['api', 'homepage']);

        $this->assertSame(['api', 'homepage'], $this->route->getGroups());
    }

    public function testCanSetAndGetAction()
    {
        $this->assertSame(null, $this->route->getAction());

        $this->route->action(fn () => 'hello world');

        $this->assertSame('hello world', $this->route->getAction()());
    }

    public function testCanGetAndSetParam()
    {
        $this->assertSame([], $this->route->getParams());

        $this->route
            ->param('x', '', new Text(10))
            ->param('y', '', new Text(10));

        $this->assertCount(2, $this->route->getParams());
    }

    public function testCanSetAndGetLabels()
    {
        $this->assertSame('default', $this->route->getLabel('key', 'default'));

        $this->route->label('key', 'value');

        $this->assertSame('value', $this->route->getLabel('key', 'default'));
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
