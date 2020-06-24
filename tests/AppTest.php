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

class AppTest extends TestCase
{
    /**
     * @var App
     */
    protected $app = null;

    public function setUp()
    {
        $this->app = new App('Asia/Tel_Aviv', App::MODE_TYPE_PRODUCTION);
    }

    public function testIsMode() {

        $this->assertEquals(App::MODE_TYPE_PRODUCTION, $this->app->getMode());
        $this->assertEquals(true, $this->app->isProduction());
        $this->assertEquals(false, $this->app->isDevelopment());
        $this->assertEquals(false, $this->app->isStage());

        $this->app->setMode(App::MODE_TYPE_PRODUCTION);

        $this->assertEquals(App::MODE_TYPE_PRODUCTION, $this->app->getMode());
        $this->assertEquals(true, $this->app->isProduction());
        $this->assertEquals(false, $this->app->isDevelopment());
        $this->assertEquals(false, $this->app->isStage());

        $this->app->setMode(App::MODE_TYPE_DEVELOPMENT);

        $this->assertEquals(App::MODE_TYPE_DEVELOPMENT, $this->app->getMode());
        $this->assertEquals(false, $this->app->isProduction());
        $this->assertEquals(true, $this->app->isDevelopment());
        $this->assertEquals(false, $this->app->isStage());

        $this->app->setMode(App::MODE_TYPE_STAGE);

        $this->assertEquals(App::MODE_TYPE_STAGE, $this->app->getMode());
        $this->assertEquals(false, $this->app->isProduction());
        $this->assertEquals(false, $this->app->isDevelopment());
        $this->assertEquals(true, $this->app->isStage());
    }

    public function testGetEnv()
    {
        // Mock
        $_SERVER['key'] = 'value';

        $this->assertEquals($this->app->getEnv('key'), 'value');
        $this->assertEquals($this->app->getEnv('unknown', 'test'), 'test');
    }

    public function tearDown()
    {
        $this->view = null;
    }
}