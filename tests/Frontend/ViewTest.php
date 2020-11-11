<?php

namespace Frontend;

use app\Frontend\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    public function testRender()
    {
        $view = new View('test/ViewTest.twig');
        $view->set('name', "Bob");

        $result = $view->render();

        $this->assertIsString($result);
        $this->assertEquals("hello Bob", $result);
    }

    /**
     * @depends testRender
     */
    public function testAsResponse()
    {
        $view = new View('test/ViewTest.twig');
        $view->set('name', "Bobbert");

        $response = $view->asResponse(418);

        $this->assertInstanceOf("app\HTTP\Response", $response);
        $this->assertSame("hello Bobbert", $response->body);
        $this->assertSame(418, $response->code);
    }
}
