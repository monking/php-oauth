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

    function testMatchRestNice() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz");
        $self = &$this;
        $this->assertTrue($h->matchRestNice("GET", "/:one/:two/:three", function($one, $two, $three) use ($self) {
            $self->assertEquals($one, "foo");
            $self->assertEquals($two, "bar");
            $self->assertEquals($three, "baz");
        }));
    }

    function testMatchRestNiceWrongMethod() {
        $h = new HttpRequest("http://www.example.org/api.php", "POST");
        $h->setPathInfo("/foo/bar/baz");
        $this->assertFalse($h->matchRestNice("GET", "/:one/:two/:three", NULL));
    }

    function testMatchRestNiceNoMatch() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz/foobar");
        $this->assertFalse($h->matchRestNice("GET", "/:one/:two/:three", NULL));
    }

    function testMatchRestNiceNoAbsPath() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("foo");
        $this->assertFalse($h->matchRestNice("GET", "foo", NULL));
    }

    function testMatchRestNiceEmptyPath() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("");
        $this->assertFalse($h->matchRestNice("GET", "", NULL));
    }

    function testMatchRestNiceEmptyRequestPath() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo");
        $this->assertFalse($h->matchRestNice("GET", "x", NULL));
    }

    function testMatchRestNiceNoMatchWithoutReplacement() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo");
        $this->assertFalse($h->matchRestNice("GET", "/bar", NULL));
    }

    function testMatchRestNiceNoMatchWithoutReplacementLong() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/foo/bar/baz");
        $this->assertFalse($h->matchRestNice("GET", "/foo/bar/foo/bar/bar", NULL));
    }

    function testMatchRestNiceEmptyResource() {
        $h = new HttpRequest("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/");
        $this->assertFalse($h->matchRestNice("GET", "/foo/:bar", NULL));
        $self = &$this;
        $h->matchDefault(function($methodMatch, $patternMatch) use ($self) {
            $self->assertEquals(array("GET"), $methodMatch);
            $self->assertFalse($patternMatch);
        });
    }

}

?>
