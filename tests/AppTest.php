<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
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
        $app = new App('Asia/Tel_Aviv', App::MODE_TYPE_PRODUCTION);

        $this->assertEquals(App::MODE_TYPE_PRODUCTION, $app->getMode());
        $this->assertEquals(true, $app->isProduction());
        $this->assertEquals(false, $app->isDevelopment());
        $this->assertEquals(false, $app->isStage());

        $app = new App('Asia/Tel_Aviv', App::MODE_TYPE_DEVELOPMENT);

        $this->assertEquals(App::MODE_TYPE_DEVELOPMENT, $app->getMode());
        $this->assertEquals(false, $app->isProduction());
        $this->assertEquals(true, $app->isDevelopment());
        $this->assertEquals(false, $app->isStage());

        $app = new App('Asia/Tel_Aviv', App::MODE_TYPE_STAGE);

        $this->assertEquals(App::MODE_TYPE_STAGE, $app->getMode());
        $this->assertEquals(false, $app->isProduction());
        $this->assertEquals(false, $app->isDevelopment());
        $this->assertEquals(true, $app->isStage());
    }

    public function tearDown()
    {
        $this->view = null;
    }
}