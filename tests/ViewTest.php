<?php

namespace Utopia;

use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    protected ?View $view;

    public function setUp(): void
    {
        $this->view = new View(__DIR__.'/mocks/View/template.phtml');
    }

    public function tearDown(): void
    {
        $this->view = null;
    }

    public function testCanSetParam()
    {
        $value = $this->view->setParam('key', 'value');

        $this->assertInstanceOf('Utopia\View', $value);
    }

    public function testCanGetParam()
    {
        $this->view->setParam('key', 'value');

        $this->assertEquals('value', $this->view->getParam('key', 'default'));
        $this->assertEquals('default', $this->view->getParam('fake', 'default'));
    }

    public function testCanSetPath()
    {
        $value = $this->view->setPath('mocks/View/fake.phtml');

        $this->assertInstanceOf('Utopia\View', $value);
    }

    public function testCanSetRendered()
    {
        $this->view->setRendered();

        $this->assertEquals(true, $this->view->isRendered());
    }

    public function testCanGetRendered()
    {
        $this->view->setRendered(false);
        $this->assertEquals(false, $this->view->isRendered());

        $this->view->setRendered(true);
        $this->assertEquals(true, $this->view->isRendered());
    }

    public function testCanRenderHtml()
    {
        $this->assertEquals('<div>Test template mock</div>', $this->view->render());

        $this->view->setRendered();
        $this->assertEquals('', $this->view->render());

        try {
            $this->view->setRendered(false);
            $this->view->setPath('just-a-broken-string.phtml');
            $this->view->render();
        } catch(\Exception $e) {
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    public function testCanEscapeUnicode()
    {
        $this->assertEquals('&amp;&quot;', $this->view->print('&"', View::FILTER_ESCAPE));
    }

    public function testCanFilterNewLinesToParagraphs()
    {
        $this->assertEquals('<p>line1</p><p>line2</p>', $this->view->print("line1\n\nline2", View::FILTER_NL2P));
    }

    public function testCanSetParamWithEscapedHtml()
    {
        $this->view->setParam('key', '<html>value</html>');
        $this->assertEquals('&lt;html&gt;value&lt;/html&gt;', $this->view->getParam('key', 'default'));
    }
}
