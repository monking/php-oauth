<?php

require_once 'OAuthHelper.php';

class AuthorizeTest extends OAuthHelper {

    /**
     * @expectedException \Tuxed\OAuth\ResourceOwnerException
     * @expectedExceptionMessage client_id missing
     */
    public function testMissingClientId() {
        $get = array();
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException \Tuxed\OAuth\ResourceOwnerException
     * @expectedExceptionMessage response_type missing
     */
    public function testMissingResponseType() {
        $get = array("client_id" => "testclient");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException \Tuxed\OAuth\ClientException
     * @expectedExceptionMessage invalid_scope
     */
    /*public function testMissingScope() {
        $get = array("client_id" => "testclient", "response_type" => "token");
        $this->_as->authorize($this->_ro, $get);
    }*/

    /**
     * @expectedException \Tuxed\OAuth\ResourceOwnerException
     * @expectedExceptionMessage client not registered
     */
    public function testUnregisteredClient() {
        $get = array("client_id" => "unregistered", "response_type" => "token", "scope" => "read");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException \Tuxed\OAuth\ResourceOwnerException
     * @expectedExceptionMessage specified redirect_uri not the same as registered redirect_uri
     */
    public function testWrongRedirectUri() {
        $get = array("client_id" => "testclient", "response_type" => "token", "scope" => "read", "redirect_uri" => "http://wrong.example.org/foo");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException \Tuxed\OAuth\ClientException
     * @expectedExceptionMessage unsupported_response_type
     */
    public function testWrongClientType() {
        $get = array("client_id" => "testclient", "response_type" => "code", "scope" => "read");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException \Tuxed\OAuth\ClientException
     * @expectedExceptionMessage invalid_scope
     */
    public function testUnsupportedScope() {
        $get = array("client_id" => "testclient", "response_type" => "token", "scope" => "foo");
        $this->_as->authorize($this->_ro, $get);
    }

    public function testCorrectCall() {
        $get = array("client_id" => "testclient", "response_type" => "token", "scope" => "read");
        $this->_as->authorize($this->_ro, $get);
    }

}
