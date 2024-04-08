<?php

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Http\Validator\Numeric;
use Utopia\Http\Validator\Text;

class HookTest extends TestCase
{
    /**
     * @var Hook
     */
    protected ?Hook $hook;

    public function setUp(): void
    {
        $this->hook = new Hook();
    }

    public function testDescriptionCanBeSet()
    {
        $this->assertEquals('', $this->hook->getDesc());

        $this->hook->desc('new hook');

        $this->assertEquals('new hook', $this->hook->getDesc());
    }

    public function testGroupsCanBeSet()
    {
        $this->assertEquals([], $this->hook->getGroups());

        $this->hook->groups(['api', 'homepage']);

        $this->assertEquals(['api', 'homepage'], $this->hook->getGroups());
    }

    public function testActionCanBeSet()
    {
        $this->hook->action(fn () => 'hello world');
        $this->assertIsCallable($this->hook->getAction());
        $this->assertEquals('hello world', $this->hook->getAction()());
    }

    public function testParamCanBeSet()
    {
        $this->assertEquals([], $this->hook->getParams());

        $this->hook
            ->param('x', '', new Text(10))
            ->param('y', '', new Text(10));

        $this->assertCount(2, $this->hook->getParams());
    }

    public function testResourcesCanBeInjected()
    {
        $main = $this->hook
            ->setName('test')
            ->inject('user')
            ->inject('time')
            ->setCallback(function ($user, $time) {
                return $user . ':' . $time;
            });

        $user = new Dependency();
        $user
            ->setName('user')
            ->setCallback(function () {
                return 'user';
            });

        $time = new Dependency();
        $time
            ->setName('time')
            ->setCallback(function () {
                return '00:00:00';
            });

        $context = new Container();

        $context
            ->set($user)
            ->set($time)
        ;

        $result = $context->inject($main);

        $this->assertEquals('user:00:00:00', $result);
    }

    public function testParamValuesCanBeSet()
    {
        $this->assertEquals([], $this->hook->getParams());

        $values = [
            'x' => 'hello',
            'y' => 'world',
        ];

        $this->hook
            ->param('x', '', new Numeric())
            ->param('y', '', new Numeric());

        foreach ($this->hook->getParams() as $key => $param) {
            $this->hook->setParamValue($key, $values[$key]);
        }

        $this->assertCount(2, $this->hook->getParams());
        $this->assertEquals('hello', $this->hook->getParams()['x']['value']);
        $this->assertEquals('world', $this->hook->getParams()['y']['value']);
    }

    public function tearDown(): void
    {
        $this->hook = null;
    }
}
