<?php

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Validator\Numeric;
use Utopia\Validator\Text;

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
        $this->assertSame('', $this->hook->getDesc());

        $this->hook->desc('new hook');

        $this->assertSame('new hook', $this->hook->getDesc());
    }

    public function testGroupsCanBeSet()
    {
        $this->assertSame([], $this->hook->getGroups());

        $this->hook->groups(['api', 'homepage']);

        $this->assertSame(['api', 'homepage'], $this->hook->getGroups());
    }

    public function testActionCanBeSet()
    {
        $this->hook->action(fn () => 'hello world');
        $this->assertIsCallable($this->hook->getAction());
        $this->assertSame('hello world', $this->hook->getAction()());
    }

    public function testParamCanBeSet()
    {
        $this->assertSame([], $this->hook->getParams());

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

        $this->assertSame('user:00:00:00', $result);
    }

    public function testParamValuesCanBeSet()
    {
        $this->assertSame([], $this->hook->getParams());

        $values = [
            'x' => 'hello',
            'y' => 'world',
        ];

        $this->hook
            ->param('x', '', new Numeric())
            ->param('y', '', new Numeric());

        /**
         * @var array $params
         */
        $params = $this->hook->getParams();

        foreach ($params as $key => $param) {
            $this->hook->setParamValue($key, $values[$key]);
        }

        $this->assertCount(2, $this->hook->getParams());
        $this->assertSame('hello', $this->hook->getParams()['x']['value']);
        $this->assertSame('world', $this->hook->getParams()['y']['value']);
    }

    public function tearDown(): void
    {
        $this->hook = null;
    }
}
