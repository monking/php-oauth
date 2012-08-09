<?php

require_once 'lib/Config.php';
require_once 'lib/OAuth/AuthorizationServer.php';
require_once 'lib/OAuth/ResourceServer.php';
require_once 'lib/OAuth/PdoOAuthStorage.php';
require_once 'lib/OAuth/DummyResourceOwner.php';

class AuthorizeTest extends PHPUnit_Framework_TestCase {

    private $_tmpDb;
    private $_ro;
    private $_as;
    private $_rs;
    private $_storage;

    public function setUp() {
        $this->_tmpDb = tempnam(sys_get_temp_dir(), "oauth_");
        if(FALSE === $this->_tmpDb) {
            throw new Exception("unable to generate temporary file for database");
        }
        $dsn = "sqlite:" . $this->_tmpDb;

        // load default config
        $c = new Config(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini.defaults");

        // override DB config in memory only
        $c->setValue("storageBackend", "PdoOAuthStorage");
        $c->setSectionValue("PdoOAuthStorage", "dsn", $dsn);

        // intialize storage
        $this->_storage = new PdoOAuthStorage($c);
        
        $this->_storage->initDatabase();

        // add a client
        $uaba = array("id" => "testclient",
                  "name" => "Simple Test Client",
                  "description" => "Client for unit testing",
                  "secret" => NULL,
                  "allowed_scope" => "read",
                  "icon" => NULL,
                  "redirect_uri" => "http://localhost/php-oauth/unit/test.html",
                  "type" => "user_agent_based_application");

        $wa = array ("id" => "testcodeclient",
                  "name" => "Simple Test Client for Authorization Code Profile",
                  "description" => "Client for unit testing",
                  "secret" => "abcdef",
                  "icon" => NULL,
                  "allowed_scope" => "read",
                  "redirect_uri" => "http://localhost/php-oauth/unit/test.html",
                  "type" => "web_application");
        $this->_storage->addClient($uaba);
        $this->_storage->addClient($wa);

        // initialize authorization server
        $this->_as = new AuthorizationServer($this->_storage, $c);
        $this->_rs = new ResourceServer($this->_storage, $c);
        $this->_ro = new DummyResourceOwner($c);
    }

    public function tearDown() {
        unlink($this->_tmpDb);
    }

    /**
     * @expectedException ResourceOwnerException
     * @expectedExceptionMessage client_id missing
     */
    public function testMissingClientId() {
        $get = array();
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException ResourceOwnerException
     * @expectedExceptionMessage response_type missing
     */
    public function testMissingResponseType() {
        $get = array("client_id" => "testclient");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException ClientException
     * @expectedExceptionMessage invalid_scope
     */
    public function testMissingScope() {
        $get = array("client_id" => "testclient", "response_type" => "token");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException ResourceOwnerException
     * @expectedExceptionMessage client not registered
     */
    public function testUnregisteredClient() {
        $get = array("client_id" => "unregistered", "response_type" => "token", "scope" => "read");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException ResourceOwnerException
     * @expectedExceptionMessage specified redirect_uri not the same as registered redirect_uri
     */
    public function testWrongRedirectUri() {
        $get = array("client_id" => "testclient", "response_type" => "token", "scope" => "read", "redirect_uri" => "http://wrong.example.org/foo");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException ClientException
     * @expectedExceptionMessage unsupported_response_type
     */
    public function testWrongClientType() {
        $get = array("client_id" => "testclient", "response_type" => "code", "scope" => "read");
        $this->_as->authorize($this->_ro, $get);
    }

    /**
     * @expectedException ClientException
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
?>
