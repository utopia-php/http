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
        $this->app = new App('Asia/Tel_Aviv');
    }

    public function testIsMode() {

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

    public function tearDown()
    {
        $this->view = null;
    }
}