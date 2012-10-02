<?php

require_once 'OAuthHelper.php';

use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\OAuth\Authorize as Authorize;

class AuthorizeTest extends OAuthHelper
{
    public function testGetAuthorize()
    {
        $h = new HttpRequest("https://auth.example.org?client_id=testclient&response_type=token&scope=read&state=xyz", "GET");
        $o = new Authorize($this->_config);
        $response = $o->handleRequest($h);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("deny", $response->getHeader("X-Frame-Options"));
    }

    public function testPostAuthorize()
    {
        $h = new HttpRequest("https://auth.example.org?client_id=testclient&response_type=token&scope=read&state=xyz", "POST");
        $h->setHeader("HTTP_REFERER", "https://auth.example.org?client_id=testclient&response_type=token&scope=read&state=xyz");
        $h->setPostParameters(array("approval" => "Approve", "scope" => array("read")));
        $o = new Authorize($this->_config);
        $response = $o->handleRequest($h);
        $this->assertEquals(302, $response->getStatusCode());
        //$this->assertEquals("", $response->getContent());
        $this->assertRegexp("|^http://localhost/php-oauth/unit/test.html#access_token=[a-zA-Z0-9]+&expires_in=5&token_type=bearer&scope=read&state=xyz$|", $response->getHeader("Location"));

        // now a get should immediately return the access token redirect...
        $h = new HttpRequest("https://auth.example.org?client_id=testclient&response_type=token&scope=read&state=abc", "GET");
        $o = new Authorize($this->_config);
        $response = $o->handleRequest($h);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertRegexp("|^http://localhost/php-oauth/unit/test.html#access_token=[a-zA-Z0-9]+&expires_in=5&token_type=bearer&scope=read&state=abc$|", $response->getHeader("Location"));
    }

    public function testUnsupportedScope()
    {
        $h = new HttpRequest("https://auth.example.org?client_id=testclient&response_type=token&scope=foo&state=xyz", "GET");
        $o = new Authorize($this->_config);
        $response = $o->handleRequest($h);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals("http://localhost/php-oauth/unit/test.html#error=invalid_scope&error_description=not+authorized+to+request+this+scope&state=xyz", $response->getHeader("Location"));
    }

    public function testUnregisteredClient()
    {
        $h = new HttpRequest("https://auth.example.org?client_id=foo&response_type=token&scope=read&state=xyz", "GET");
        $o = new Authorize($this->_config);
        $response = $o->handleRequest($h);
        $this->assertEquals(400, $response->getStatusCode());
        //$this->assertRegexp("|.*client not registered.*$|", $response->getContent());
    }

    public function testInvalidRequestMethod()
    {
        $h = new HttpRequest("https://auth.example.org?client_id=foo&response_type=token&scope=read&state=xyz", "DELETE");
        $o = new Authorize($this->_config);
        $response = $o->handleRequest($h);
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testCSRFAttack()
    {
        $h = new HttpRequest("https://auth.example.org?client_id=testclient&response_type=token&scope=read&state=xyz", "POST");
        $h->setHeader("HTTP_REFERER", "https://evil.site.org/xyz");
        $h->setPostParameters(array("approval" => "Approve", "scope" => array("read")));
        $o = new Authorize($this->_config);
        $response = $o->handleRequest($h);
        $this->assertEquals(400, $response->getStatusCode());
        //$this->assertRegexp("|.*csrf protection triggered, referrer does not match request uri.*|$|", $response->getContent());
    }

#    /**
#     * @expectedException \Tuxed\OAuth\ResourceOwnerException
#     * @expectedExceptionMessage client_id missing
#     */
#    public function testMissingClientId() {
#        $get = array();
#        $this->_as->authorize($this->_ro, $get);
#    }

#    /**
#     * @expectedException \Tuxed\OAuth\ResourceOwnerException
#     * @expectedExceptionMessage response_type missing
#     */
#    public function testMissingResponseType() {
#        $get = array("client_id" => "testclient");
#        $this->_as->authorize($this->_ro, $get);
#    }

#    public function testWithoutScope() {
#        $get = array("client_id" => "testclient", "response_type" => "token");
#        $this->_as->authorize($this->_ro, $get);
#    }

#    /**
#     * @expectedException \Tuxed\OAuth\ResourceOwnerException
#     * @expectedExceptionMessage client not registered
#     */
#    public function testUnregisteredClient() {
#        $get = array("client_id" => "unregistered", "response_type" => "token", "scope" => "read");
#        $this->_as->authorize($this->_ro, $get);
#    }

#    /**
#     * @expectedException \Tuxed\OAuth\ResourceOwnerException
#     * @expectedExceptionMessage specified redirect_uri not the same as registered redirect_uri
#     */
#    public function testWrongRedirectUri() {
#        $get = array("client_id" => "testclient", "response_type" => "token", "scope" => "read", "redirect_uri" => "http://wrong.example.org/foo");
#        $this->_as->authorize($this->_ro, $get);
#    }

#    /**
#     * @expectedException \Tuxed\OAuth\ClientException
#     * @expectedExceptionMessage unsupported_response_type
#     */
#    public function testWrongClientType() {
#        $get = array("client_id" => "testclient", "response_type" => "code", "scope" => "read");
#        $this->_as->authorize($this->_ro, $get);
#    }

#    /**
#     * @expectedException \Tuxed\OAuth\ClientException
#     * @expectedExceptionMessage invalid_scope
#     */
#    public function testUnsupportedScope() {
#        $get = array("client_id" => "testclient", "response_type" => "token", "scope" => "foo");
#        $this->_as->authorize($this->_ro, $get);
#    }

#    public function testCorrectCall() {
#        $get = array("client_id" => "testclient", "response_type" => "token", "scope" => "read");
#        $this->_as->authorize($this->_ro, $get);
#    }

}
