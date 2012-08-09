<?php

require_once "lib/Http/HttpRequest.php";
require_once "lib/Http/IncomingHttpRequest.php";

class IncomingHttpRequestTest extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider getDataProvider
     */
    function testGetRequests($port, $name, $request, $https, $request_uri) {
        $_SERVER['SERVER_PORT'] = $port;
        $_SERVER['SERVER_NAME'] = $name;
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['REQUEST_METHOD'] = "GET";
        $_SERVER['HTTPS'] = $https;

        $stub = $this->getMock('IncomingHttpRequest', array('getRequestHeaders'));
        $stub->expects($this->any())
                ->method('getRequestHeaders')
                ->will($this->returnValue(array("A" => "B")));
        // $this->assertInstanceOf("HttpRequest", $stub->getRequest());
        $request = HttpRequest::fromIncomingHttpRequest($stub);
        $this->assertEquals($request_uri, $request->getRequestUri()->getUri());
        $this->assertEquals("GET", $request->getRequestMethod());
    }

    function getDataProvider() {
        return array(
            array("80", "www.example.com", "/request", "off", "http://www.example.com/request"),
            array("443", "www.example.com", "/request", "off", "http://www.example.com:443/request"),
            array("443", "www.example.com", "/request", "on", "https://www.example.com/request"),
            array("80", "www.example.com", "/request", "on", "https://www.example.com:80/request"),
                // can not do IPv6 literals :( 
                // PHP missing feature (bug)
                // array ("80", "2001:610::4", "/request", "off", "http://[2001:610::4]/request"),
                // array ("443", "2001:610::4", "/request", "on", "https://[2001:610::4]/request"),
                // array ("8080", "2001:610::4", "/request", "off", "http://[2001:610::4]:8080/request"),
        );
    }

    /**
     * @dataProvider postDataProvider
     */
    function testPostRequests($port, $name, $request, $https, $request_uri, $content) {
        $_SERVER['SERVER_PORT'] = $port;
        $_SERVER['SERVER_NAME'] = $name;
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['REQUEST_METHOD'] = "POST";
        $_SERVER['CONTENT_LENGTH'] = strlen($content);
        $_SERVER['HTTPS'] = $https;

        $stub = $this->getMock('IncomingHttpRequest', array('getRequestHeaders', 'getRawContent'));
        $stub->expects($this->any())
                ->method('getRequestHeaders')
                ->will($this->returnValue(array("A" => "B")));

        $stub->expects($this->any())
                ->method('getRawContent')
                ->will($this->returnValue($content));

        //$this->assertInstanceOf('HttpRequest', $stub->getRequest());
        
        $request = HttpRequest::fromIncomingHttpRequest($stub);
        $this->assertEquals($request_uri, $request->getRequestUri()->getUri());
        $this->assertEquals("POST", $request->getRequestMethod());
        $this->assertEquals($content, $request->getContent());
    }

    function postDataProvider() {
        return array(
            array("80", "www.example.com", "/request", "off", "http://www.example.com/request", ""),
            array("80", "www.example.com", "/request", "off", "http://www.example.com/request", "action=foo"),
            array("443", "www.example.com", "/request", "on", "https://www.example.com/request", "action=foo"),
            array("80", "www.example.com", "/request", "off", "http://www.example.com/request", pack("nvc*", 0x1234, 0x5678, 65, 66)),
        );
    }

    /**
     * @expectedException IncomingHttpRequestException
     */
    function testNoServer() {
        $i = new IncomingHttpRequest();
        $r = $i->getRequest();
    }

    /* function testEmptyContent() {
      $_SERVER['SERVER_PORT'] = $port;
      $_SERVER['SERVER_NAME'] = $name;
      $_SERVER['REQUEST_URI'] = $request;
      $_SERVER['REQUEST_METHOD'] = "GET";
      $i = new IncomingHttpRequest();
      $r = $i->getRequest();
     */
}

?>
