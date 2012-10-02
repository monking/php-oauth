<?php

require_once 'lib/SplClassLoader.php';
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

use \Tuxed\Http\Uri as Uri;
use \Tuxed\Http\UriException as UriException;

class UriTest extends PHPUnit_Framework_TestCase
{
    public function testSetFragment()
    {
        $h = new Uri("http://www.example.com/request?action=foo");
        $h->setFragment("bar=foo");
        $this->assertEquals("http://www.example.com/request?action=foo#bar=foo", $h->getUri());
    }

    public function testUser()
    {
        $h = new Uri("http://user@www.example.com/");
        $h->setFragment("bar=foo");
        $this->assertEquals("http://user@www.example.com/#bar=foo", $h->getUri());
    }

    public function testUserPass()
    {
        $h = new Uri("http://user:pass@www.example.com/");
        $h->setFragment("bar=foo");
        $this->assertEquals("http://user:pass@www.example.com/#bar=foo", $h->getUri());
    }

    public function testAppendQuery()
    {
        $h = new Uri("http://www.example.com/request?action=foo");
        $h->appendQuery("bar=foo&do=true");
        $this->assertEquals("http://www.example.com/request?action=foo&bar=foo&do=true", $h->getUri());
    }

    public function testAppendNullQuery()
    {
        $h = new Uri("http://www.example.com/request");
        $h->appendQuery("bar=foo&do=true");
        $this->assertEquals("http://www.example.com/request?bar=foo&do=true", $h->getUri());
    }

    public function testOtherPort()
    {
        $h = new Uri("http://www.example.com:443/request");
        $h->setQuery("x");
        $this->assertEquals("http://www.example.com:443/request?x", $h->getUri());
    }

    public function testWithGetParameters()
    {
        $h = new Uri("http://www.example.com/request?action=foo&user=admin&password=secret");
        $this->assertEquals("http", $h->getScheme());
        $this->assertEquals("www.example.com", $h->getHost());
        $this->assertEquals("/request", $h->getPath());
        $this->assertEquals("action=foo&user=admin&password=secret", $h->getQuery());
    }

    public function testHttpOtherPort()
    {
        $h = new Uri("http://www.example.com:8080/request");
        $this->assertEquals("http", $h->getScheme());
        $this->assertEquals("www.example.com", $h->getHost());
        $this->assertEquals(8080, $h->getPort());
        $this->assertEquals("/request", $h->getPath());
    }

    public function testHttpWithHttpsPort()
    {
        $h = new Uri("http://www.example.com:443/request");
        $this->assertEquals("http", $h->getScheme());
        $this->assertEquals("www.example.com", $h->getHost());
        $this->assertEquals(443, $h->getPort());
        $this->assertEquals("/request", $h->getPath());
    }

    public function testHttpsWithHttpPort()
    {
        $h = new Uri("https://www.example.com:80/request");
        $this->assertEquals("https", $h->getScheme());
        $this->assertEquals("www.example.com", $h->getHost());
        $this->assertEquals(80, $h->getPort());
        $this->assertEquals("/request", $h->getPath());
    }

    public function testHttpsWithoutPath()
    {
        $h = new Uri("https://www.example.com/");
        $this->assertEquals("https", $h->getScheme());
        $this->assertEquals("www.example.com", $h->getHost());
        $this->assertEquals("/", $h->getPath());
    }

    public function testHttpsWithOtherPortAndQuery()
    {
        $h = new Uri("https://www.example.com:8081/request?action=foo");
        $this->assertEquals("https", $h->getScheme());
        $this->assertEquals("www.example.com", $h->getHost());
        $this->assertEquals(8081, $h->getPort());
        $this->assertEquals("/request", $h->getPath());
    }

    public function testHttpsWithOtherPortNoPathAndQuery()
    {
        $h = new Uri("https://www.example.com:8081/?action=foo");
        $this->assertEquals("https", $h->getScheme());
        $this->assertEquals("www.example.com", $h->getHost());
        $this->assertEquals(8081, $h->getPort());
        $this->assertEquals("/", $h->getPath());
        $this->assertEquals("action=foo", $h->getQuery());
    }

    /**
     * @expectedException \Tuxed\Http\UriException
     */
    public function testMalformedUri()
    {
        $h = new Uri("http://:80");
    }

}
