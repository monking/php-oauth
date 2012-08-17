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

    function testCollection() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/bar");
        $this->assertEquals("foo", $h->getCollection());
    }

    function testOtherCollection() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/bar/foobar");
        $this->assertEquals("foo/bar", $h->getCollection());
    }

    function testResource() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/bar");
        $this->assertEquals("bar", $h->getResource());
    }
    
    function testMissingResource() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/");
        $this->assertFalse($h->getResource());
    }

    function testMissingCollection() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/");
        $this->assertFalse($h->getCollection());
    }

    function testMissingResourceRoot() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/");
        $this->assertFalse($h->getResource());
    }

    function testMissingCollectionPathInfo() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $this->assertFalse($h->getCollection());
    }

    function testMissingResourcePathInfo() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $this->assertFalse($h->getResource());
    }

    function testWeirdPathInfoCollection() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("foo");
        $this->assertFalse($h->getCollection());
    }

    function testWeirdPathInfoResource() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("foo");
        $this->assertFalse($h->getResource());
    }

    function testMatchRest() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/bar");
        $this->assertTrue($h->matchRest("POST", "foo", TRUE));
    }

    function testMatchRestSpecificResource() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/bar");
        $this->assertTrue($h->matchRest("POST", "foo", "bar"));
    }

    function testMatchRestNoResource() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/");
        $this->assertTrue($h->matchRest("POST", "foo", FALSE));
    }

    function testMatchRestNonMatchingCollection() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/bar");
        $this->assertFalse($h->matchRest("POST", "bar", TRUE));
    }

    function testNonMatchingResource() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setPathInfo("/foo/bar");
        $this->assertFalse($h->matchRest("POST", "foo", "foo"));
    }

    function testMatchRestNonMatchingRequestMethod() {
        $h = new HttpRequest("http://www.example.com/request", "GET");
        $h->setPathInfo("/foo/bar");
        $this->assertFalse($h->matchRest("POST", "foo", TRUE));
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

    function testForNoQueryValue() {
        $h = new HttpRequest("http://www.example.com/request?foo=&bar=&foobar=xyz");
        $this->assertNull($h->getQueryParameter("foo"));
        $this->assertNull($h->getQueryParameter("bar"));
        $this->assertEquals("xyz", $h->getQueryParameter("foobar"));
    }

}

?>
