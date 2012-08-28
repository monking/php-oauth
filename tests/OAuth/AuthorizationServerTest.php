<?php

require_once 'OAuthHelper.php';

use \Tuxed\OAuth\ResourceServerException as ResourceServerException;

class ImplicitGrantTest extends OAuthHelper {

    public function testImplicitGrant() {
        // now we ask the authorize endpoint
        $get = array("client_id" => "testclient", 
                     "response_type" => "token",
                     "scope" => "read");
        $response = $this->_as->authorize($this->_ro, $get);
        $action = $response['action'];
        $client = $response['client'];

        $this->assertEquals("ask_approval", $action);
        $this->assertEquals("testclient", $client->id);

        // now we approve
        $post = array("approval" => "Approve", "scope" => array("read"));
        $response = $this->_as->approve($this->_ro, $get, $post);

        $action = $response['action'];
        $url = $response['url'];

        $this->assertEquals("redirect", $action);
        // regexp match to deal with random access token
        $this->assertRegExp('|^http://localhost/php-oauth/unit/test.html#access_token=[a-zA-Z0-9]+&expires_in=5&token_type=bearer&scope=read$|', $url);
    }

    public function testAuthorizationCode() {
        $get = array("client_id" => "testcodeclient", 
                     "response_type" => "code",
                     "scope" => "read");
        $response = $this->_as->authorize($this->_ro, $get);
        $action = $response['action'];
        $client = $response['client'];

        $this->assertEquals("ask_approval", $action);
        $this->assertEquals("testcodeclient", $client->id);

        // now we approve
        $post = array("approval" => "Approve", "scope" => array("read"));
        $response = $this->_as->approve($this->_ro, $get, $post);

        $action = $response['action'];
        $url = $response['url'];

        $this->assertEquals("redirect", $action);
        // regexp match to deal with random authorization code
        $this->assertRegExp('|^http://localhost/php-oauth/unit/test.html\?code=[a-zA-Z0-9]+$|', $url);

        preg_match('|^http://localhost/php-oauth/unit/test.html\?code=([a-zA-Z0-9]+)$|', $url, $matches);
        
        // exchange code for token
        $post = array ("grant_type" => "authorization_code",
                       "code" => $matches[1]);
        $response = $this->_as->token($post, "testcodeclient", "abcdef");

        $this->assertRegExp('|^[a-zA-Z0-9]+$|', $response->access_token);
        $this->assertEquals(5, $response->expires_in);
        $this->assertRegExp('|^[a-zA-Z0-9]+$|', $response->refresh_token);
        $this->assertEquals("read", $response->scope);
        // .. redirect_uri

        // verify token
        $this->_rs->verifyAuthorizationHeader("Bearer " . $response->access_token);
        $this->assertEquals("fkooman", $this->_rs->getResourceOwnerId());
        $this->assertTrue($this->_rs->hasEntitlement("applications"));
        $this->assertFalse($this->_rs->hasEntitlement("foo"));
        $this->assertTrue($this->_rs->hasScope("read"));
        $this->assertFalse($this->_rs->hasScope("foo"));

        try {
            $this->_rs->requireScope("foo");
            $this->assertTrue(FALSE);
        } catch(ResourceServerException $e) {
            $this->assertEquals("insufficient_scope", $e->getMessage());
            $this->assertEquals("no permission for this call with granted scope", $e->getDescription());
        }

        try {
            $this->_rs->requireEntitlement("foo");
            $this->assertTrue(FALSE);
        } catch(ResourceServerException $e) {
            $this->assertEquals("insufficient_entitlement", $e->getMessage());
            $this->assertEquals("no permission for this call with granted entitlement", $e->getDescription());
        }

        // wait for 6 seconds so the token should be expired...
        sleep(6);

        try { 
            $this->_rs->verifyAuthorizationHeader("Bearer " . $response->access_token);
            $this->assertTrue(FALSE);
        } catch(ResourceServerException $e) {
            $this->assertEquals("invalid_token", $e->getMessage());
            $this->assertEquals("the access token expired", $e->getDescription());
        }

        // use the refresh token to get a new access token
        $post = array ("grant_type" => "refresh_token",
                       "refresh_token" => $response->refresh_token);
        $response = $this->_as->token($post, "testcodeclient", "abcdef");
        
        $this->assertRegExp('|^[a-zA-Z0-9]+$|', $response->access_token);
        $this->_rs->verifyAuthorizationHeader("Bearer " . $response->access_token);
        $this->assertEquals("fkooman", $this->_rs->getResourceOwnerId());
    }

}
?>
