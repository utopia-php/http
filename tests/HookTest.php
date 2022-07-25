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

class HookTest extends TestCase
{
    /**
     * @var Hook
     */
    protected $hook;

    public function setUp():void
    {
        $this->hook = new Hook();
    }

    public function testDesc()
    {
        $this->assertEquals('', $this->hook->getDesc());
        
        $this->hook->desc('new hook');

        $this->assertEquals('new hook', $this->hook->getDesc());
    }

    public function testGroups()
    {
        $this->assertEquals([], $this->hook->getGroups());
        
        $this->hook->groups(['api', 'homepage']);

        $this->assertEquals(['api', 'homepage'], $this->hook->getGroups());
    }

    public function testAction()
    {
        $this->assertEquals(function(): void {}, $this->hook->getAction());
        
        $this->hook->action(function() {return 'hello world';});

        $this->assertEquals('hello world', $this->hook->getAction()());
    }

    public function testParam()
    {
        $this->assertEquals([], $this->hook->getParams());
        
        $this->hook
            ->param('x', '', new Text(10))
            ->param('y', '', new Text(10))
        ;

        $this->assertCount(2, $this->hook->getParams());
    }

    public function testResources()
    {
        $this->assertEquals([], $this->hook->getInjections());
        
        $this->hook
            ->inject('user')
            ->inject('time')
            ->action(function() {})
        ;

        $this->assertCount(2, $this->hook->getInjections());
        $this->assertEquals('user', $this->hook->getInjections()['user']['name']);
        $this->assertEquals('time', $this->hook->getInjections()['time']['name']);
    }

    public function tearDown():void
    {
        $this->hook = null;
    }
}