<?php

require_once "lib/SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

use \Tuxed\Http\HttpRequestException as HttpRequestException;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\Http\Uri as Uri;
use \Tuxed\Http\UriException as UriException;

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
     * @expectedException \Tuxed\Http\HttpRequestException
     */
    function testTryGetPostParametersOnGetRequest() {
        $h = new HttpRequest("http://www.example.com/request", "GET");
        $h->getPostParameters();
    }

    /**
     * @expectedException \Tuxed\Http\HttpRequestException
     */
    function testTrySetPostParametersOnGetRequest() {
        $h = new HttpRequest("http://www.example.com/request", "GET");
        $h->setPostParameters(array("action" => "test"));
    }

    /**
     * @expectedException \Tuxed\Http\HttpRequestException
     */
/*    function testTryGetPostParametersWithoutParameters() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->getPostParameters();
    }*/

    /**
     * @expectedException \Tuxed\Http\HttpRequestException
     */
/*    function testTryGetPostParametersWithRawContent() {
        $h = new HttpRequest("http://www.example.com/request", "POST");
        $h->setContent("Hello World!");
        $h->getPostParameters();
    }*/

    /**
     * @expectedException \Tuxed\Http\UriException
     */
    function testInvalidUri() {
        $h = new HttpRequest("foo");
    }

    /**
     * @expectedException \Tuxed\Http\HttpRequestException
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

    function testMatchRest() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz");
        $self = &$this;
        $this->assertTrue($h->matchRest("GET", "/:one/:two/:three", function($one, $two, $three) use ($self) {
            $self->assertEquals($one, "foo");
            $self->assertEquals($two, "bar");
            $self->assertEquals($three, "baz");
        }));
    }

    function testMatchRestWrongMethod() {
        $h = new HttpRequest("http://www.example.org/api.php", "POST");
        $h->setPathInfo("/foo/bar/baz");
        $this->assertFalse($h->matchRest("GET", "/:one/:two/:three", NULL));
    }

    function testMatchRestNoMatch() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz/foobar");
        $this->assertFalse($h->matchRest("GET", "/:one/:two/:three", NULL));
    }

    function testMatchRestNoAbsPath() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("foo");
        $this->assertFalse($h->matchRest("GET", "foo", NULL));
    }

    function testMatchRestEmptyPath() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("");
        $this->assertFalse($h->matchRest("GET", "", NULL));
    }

    function testMatchRestEmptyRequestPath() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo");
        $this->assertFalse($h->matchRest("GET", "x", NULL));
    }

    function testMatchRestNoMatchWithoutReplacement() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo");
        $this->assertFalse($h->matchRest("GET", "/bar", NULL));
    }

    function testMatchRestNoMatchWithoutReplacementLong() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/foo/bar/baz");
        $this->assertFalse($h->matchRest("GET", "/foo/bar/foo/bar/bar", NULL));
    }

    function testMatchRestEmptyResource() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/");
        $this->assertFalse($h->matchRest("GET", "/foo/:bar", NULL));
        $self = &$this;
        $h->matchRestDefault(function($methodMatch, $patternMatch) use ($self) {
            $self->assertEquals(array("GET"), $methodMatch);
            $self->assertFalse($patternMatch);
        });
    }

}

?>
