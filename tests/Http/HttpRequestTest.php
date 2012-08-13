<?php

require_once "lib/Http/HttpRequest.php";

class HttpRequestTest extends PHPUnit_Framework_TestCase {

    function testHttpRequest() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPostParameters(array("id" => 5, "action" => "help"));
        $this->assertEquals("http://www.example.com/request", $h->getRequestUri()->getUri());
        $this->assertEquals("POST", $h->getRequestMethod());
        $this->assertEquals("id=5&action=help", $h->getContent());
        $this->assertEquals("application/x-www-form-urlencoded", $h->getHeader("Content-type"));
        $this->assertEquals(array("id" => 5, "action" => "help"), $h->getPostParameters());
    }

    function testHttpQueryParameters() {
        $h = new HttpRequest("http://www.example.com/request?action=foo&method=bar", "GET");
        $this->assertEquals(array("action" => "foo", "method" => "bar"), $h->getQueryParameters());
    }

    function testHttpQueryParametersWithoutParameters() {
        $h = new HttpRequest("http://www.example.com/request", "GET");
        $this->assertEquals(array(), $h->getQueryParameters());
    }

    function testHttpUriParametersWithPost() {
        $h = new HttpRequest("http://www.example.com/request?action=foo&method=bar", "POST");
        $h->setPostParameters(array("id" => 5, "action" => "help"));
        $this->assertEquals(array("action" => "foo", "method" => "bar"), $h->getQueryParameters());
        $this->assertEquals(array("id" => 5, "action" => "help"), $h->getPostParameters());
    }

    function testSetHeaders() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setHeader("A", "B");
        $h->setHeader("foo", "bar");
        $this->assertEquals("B", $h->getHeader("A"));
        $this->assertEquals("bar", $h->getHeader("foo"));
        $this->assertEquals(array("A" => "B", "foo" => "bar"), $h->getHeaders(FALSE));
        $this->assertEquals(array("A: B", "foo: bar"), $h->getHeaders(TRUE));
    }

    function testSetGetHeadersCaseInsensitive() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setHeader("Content-type", "application/json");
        $h->setHeader("Content-Type", "text/html"); // this overwrites the previous one
        $this->assertEquals("text/html", $h->getHeader("CONTENT-TYPE"));
    }

    /**
     * @expectedException HttpRequestException
     */
    function testTryGetPostParametersOnGetRequest() {
        $h = new HttpRequest("http://www.example.com/request", "GET");
        $h->getPostParameters();
    }

    /**
     * @expectedException HttpRequestException
     */
    function testTrySetPostParametersOnGetRequest() {
        $h = new HttpRequest("http://www.example.com/request", "GET");
        $h->setPostParameters(array("action" => "test"));
    }

    /**
     * @expectedException HttpRequestException
     */
/*    function testTryGetPostParametersWithoutParameters() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->getPostParameters();
    }*/

    /**
     * @expectedException HttpRequestException
     */
/*    function testTryGetPostParametersWithRawContent() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setContent("Hello World!");
        $h->getPostParameters();
    }*/

    /**
     * @expectedException UriException
     */
    function testInvalidUri() {
        $h = new HttpRequest("foo");
    }

    /**
     * @expectedException HttpRequestException
     */
    function testUnsupportedRequestMethod() {
        $h = new HttpRequest("http://www.example.com/request", "FOO");
    }

    function testNonExistingHeader() {
        $h = new HttpRequest("http://www.example.com/request");
        $this->assertNull($h->getHeader("Authorization"));
    }

    function testForHeaderDoesNotExist() {
        $h = new HttpRequest("http://www.example.com/request");
        $this->assertNull($h->getHeader("Authorization"));
    }

    function testForHeaderDoesExist() {
        $h = new HttpRequest("http://www.example.com/request");
        $h->setHeader("Authorization", "Bla");
        $this->assertNotNull($h->getHeader("Authorization"));
    }

}

?>
