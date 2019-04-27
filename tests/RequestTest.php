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

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Request;

class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    protected $request = null;

    public function setUp()
    {
        $this->request = new Request();
    }

    public function tearDown()
    {
        $this->request = null;
    }

    public function testGetQuery()
    {
        // Mock
        $_GET['key'] = 'value';

        // Assertions
        $this->assertEquals($this->request->getQuery('key'), 'value');
        $this->assertEquals($this->request->getQuery('unknown', 'test'), 'test');
    }

    public function testGetPayload()
    {
        //Assertions
        $this->assertEquals($this->request->getPayload('unknown', 'test'), 'test');
    }

    public function testGetRequest()
    {
        // Mock
        $_REQUEST['key'] = 'value';

        // Assertions
        $this->assertEquals($this->request->getRequest('key'), 'value');
        $this->assertEquals($this->request->getRequest('unknown', 'test'), 'test');
    }

    public function testGetServer()
    {
        // Mock
        $_SERVER['key'] = 'value';

        // Assertions
        $this->assertEquals($this->request->getServer('key'), 'value');
        $this->assertEquals($this->request->getServer('unknown', 'test'), 'test');
    }

    public function testGetCookie()
    {
        // Mock
        $_COOKIE['key'] = 'value';

        // Assertions
        $this->assertEquals($this->request->getCookie('key'), 'value');
        $this->assertEquals($this->request->getCookie('unknown', 'test'), 'test');
    }

/*    public function testGetHeader()
    {
        // Assertions
        //$this->assertEquals($this->request->getHeader('key', 'value'), 'value');
    }*/
}
