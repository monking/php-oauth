<?php

require_once 'OAuthHelper.php';

use \Tuxed\OAuth\Api as Api;
use \Tuxed\OAuth\ApiException as ApiException;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\OAuth\ResourceServerException as ResourceServerException;

class ApiTest extends OAuthHelper {

    protected $_api;

    public function setUp() {
        parent::setUp();
        // enable Api
        $this->_config->setSectionValue("Api", "enableApi", TRUE);
        $this->_api = new Api($this->_config, NULL);

        $oauthStorageBackend = '\\Tuxed\OAuth\\' . $this->_config->getValue('storageBackend');
        $storage = new $oauthStorageBackend($this->_config);

        $storage->updateEntitlement('fkooman', NULL);
        $storage->addApproval('testclient', 'fkooman', 'read', NULL);
        $storage->storeAccessToken('12345abc', time(), 'testcodeclient', 'fkooman', 'authorizations', 3600);

    }

    public function testRetrieveAuthorizations() {
        $h = new HttpRequest("http://www.example.org/api.php");
        $h->setPathInfo("/authorizations/");
        $h->setHeader("Authorization", "Bearer 12345abc");
        $response = $this->_api->handleRequest($h);
        $this->assertEquals('[{"scope":"read","id":"testclient","name":"Simple Test Client","description":"Client for unit testing","redirect_uri":"http:\/\/localhost\/php-oauth\/unit\/test.html","type":"user_agent_based_application","icon":null,"allowed_scope":"read"}]', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->getHeader("Content-Type"));
    }

    public function testAddAuthorizations() {
        $h = new HttpRequest("http://www.example.org/api.php");
        $h->setRequestMethod("POST");
        $h->setPathInfo("/authorizations/");
        $h->setHeader("Authorization", "Bearer 12345abc");
        $h->setContent(json_encode(array("client_id" => "testcodeclient", "scope" => "read", "refresh_token" => NULL)));
        $response = $this->_api->handleRequest($h);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testAddAuthorizationsUnregisteredClient() {
        $h = new HttpRequest("http://www.example.org/api.php");
        $h->setRequestMethod("POST");
        $h->setPathInfo("/authorizations/");
        $h->setHeader("Authorization", "Bearer 12345abc");
        $h->setContent(json_encode(array("client_id" => "nonexistingclient", "scope" => "read")));
        $response = $this->_api->handleRequest($h);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"invalid_request","error_description":"client is not registered"}', $response->getContent());
    }

    public function testAddAuthorizationsUnsupportedScope() {
        $h = new HttpRequest("http://www.example.org/api.php");
        $h->setRequestMethod("POST");
        $h->setPathInfo("/authorizations/");
        $h->setHeader("Authorization", "Bearer 12345abc");
        $h->setContent(json_encode(array("client_id" => "testcodeclient", "scope" => "foo")));
        $response = $this->_api->handleRequest($h);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"invalid_request","error_description":"invalid scope for this client"}', $response->getContent());
    }

    public function testGetAuthorization() {
        $h = new HttpRequest("http://www.example.org/api.php");
        $h->setPathInfo("/authorizations/testclient");
        $h->setHeader("Authorization", "Bearer 12345abc");
        // FIXME: test with non existing client_id!
        $response = $this->_api->handleRequest($h);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"client_id":"testclient","resource_owner_id":"fkooman","scope":"read","refresh_token":null}', $response->getContent());
    }

    public function testDeleteAuthorization() {
        $h = new HttpRequest("http://www.example.org/api.php");
        $h->setRequestMethod("DELETE");
        $h->setPathInfo("/authorizations/testclient");
        $h->setHeader("Authorization", "Bearer 12345abc");
        // FIXME: test with non existing client_id!
        $response = $this->_api->handleRequest($h);
        $this->assertEquals(200, $response->getStatusCode());
    }

}
