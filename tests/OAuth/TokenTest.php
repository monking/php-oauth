<?php

require_once 'OAuthHelper.php';

use \RestService\Http\HttpRequest as HttpRequest;
use \OAuth\Token as Token;

class TokenTest extends OAuthHelper
{
    public function setUp()
    {
        parent::setUp();

        $oauthStorageBackend = 'OAuth\\' . $this->_config->getValue('storageBackend');
        $storage = new $oauthStorageBackend($this->_config);

        $storage->updateResourceOwner('fkooman', NULL, NULL);
        $storage->addApproval('testcodeclient', 'fkooman', 'read', 'r3fr3sh');
        //$storage->storeAccessToken('12345abc', time(), 'testcodeclient', 'fkooman', 'authorizations', 3600);
        $storage->storeAuthorizationCode("4uth0r1z4t10n", "fkooman", time(), "testcodeclient", NULL, "read");

    }
    public function testAuthorizationCode()
    {
        $h = new HttpRequest("https://auth.example.org/token", "POST");
        $h->setHeader("Authorization", "Basic " . base64_encode("testcodeclient:abcdef"));
        $h->setPostParameters(array("code" => "4uth0r1z4t10n", "grant_type" => "authorization_code"));
        $t = new Token($this->_config, NULL);
        $response = $t->handleRequest($h);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegexp('|^{"access_token":"[a-zA-Z0-9]+","expires_in":5,"scope":"read","refresh_token":"r3fr3sh","token_type":"bearer"}$|', $response->getContent());
    }

    public function testRefreshToken()
    {
        $h = new HttpRequest("https://auth.example.org/token", "POST");
        $h->setHeader("Authorization", "Basic " . base64_encode("testcodeclient:abcdef"));
        $h->setPostParameters(array("refresh_token" => "r3fr3sh", "grant_type" => "refresh_token"));
        $t = new Token($this->_config, NULL);
        $response = $t->handleRequest($h);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegexp('|^{"access_token":"[a-zA-Z0-9]+","expires_in":5,"scope":"read","token_type":"bearer"}$|', $response->getContent());
    }

    public function testInvalidRequestMethod()
    {
        $h = new HttpRequest("https://auth.example.org?client_id=foo&response_type=token&scope=read&state=xyz", "GET");
        $o = new Token($this->_config);
        $response = $o->handleRequest($h);
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testWithoutGrantType()
    {
        $h = new HttpRequest("https://auth.example.org/token", "POST");
        $h->setHeader("Authorization", "Basic " . base64_encode("testcodeclient:abcdef"));
        $h->setPostParameters(array("code" => "4uth0r1z4t10n"));
        $t = new Token($this->_config, NULL);
        $response = $t->handleRequest($h);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"invalid_request","error_description":"the grant_type parameter is missing"}', $response->getContent());
    }

    public function testWithoutCredentials()
    {
        $h = new HttpRequest("https://auth.example.org/token", "POST");
        $h->setPostParameters(array("client_id" => "testcodeclient", "code" => "4uth0r1z4t10n", "grant_type" => "authorization_code"));
        $t = new Token($this->_config, NULL);
        $response = $t->handleRequest($h);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"error":"invalid_client","error_description":"this client requires authentication"}', $response->getContent());

    }

}
