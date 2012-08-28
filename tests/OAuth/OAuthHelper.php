<?php

require_once "lib/SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\OAuth\PdoOAuthStorage as PdoOAuthStorage;
use \Tuxed\OAuth\AuthorizationServer as AuthorizationServer;
use \Tuxed\OAuth\ResourceServer as ResourceServer;
use \Tuxed\OAuth\ResourceServerException as ResourceServerException;
use \Tuxed\OAuth\DummyResourceOwner as DummyResourceOwner;

class OAuthHelper extends PHPUnit_Framework_TestCase {

    protected $_tmpDb;
    protected $_ro;
    protected $_as;
    protected $_rs;
    protected $_storage;
    protected $_config;

    public function setUp() {
        $this->_tmpDb = tempnam(sys_get_temp_dir(), "oauth_");
        if(FALSE === $this->_tmpDb) {
            throw new Exception("unable to generate temporary file for database");
        }
        $dsn = "sqlite:" . $this->_tmpDb;

        // load default config
        $this->_config = new Config(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini.defaults");

        $this->_config->setValue("accessTokenExpiry", 5);

        // override DB config in memory only
        $this->_config->setValue("storageBackend", "PdoOAuthStorage");
        $this->_config->setSectionValue("PdoOAuthStorage", "dsn", $dsn);

        //$this->_config->setSectionValue("DummyResourceOwner", "resourceOwnerEntitlement") = array ("foo" => array("fkooman"));

        // intialize storage
        $this->_storage = new PdoOAuthStorage($this->_config);
        
        $this->_storage->initDatabase();

        // add a client
        $uaba = array("id" => "testclient",
                  "name" => "Simple Test Client",
                  "description" => "Client for unit testing",
                  "secret" => NULL,
                  "icon" => NULL,
                  "allowed_scope" => "read",
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
        $this->_as = new AuthorizationServer($this->_storage, $this->_config);
        $this->_rs = new ResourceServer($this->_storage);
        $this->_ro = new DummyResourceOwner($this->_config);
    }

    public function tearDown() {
        unlink($this->_tmpDb);
    }

    public function testNop() {

    }

}
