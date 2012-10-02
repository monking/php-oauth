<?php

require_once 'lib/SplClassLoader.php';
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\OAuth\PdoOAuthStorage as PdoOAuthStorage;

class OAuthHelper extends PHPUnit_Framework_TestCase
{
    protected $_tmpDb;
    protected $_config;

    public function setUp()
    {
        $this->_tmpDb = tempnam(sys_get_temp_dir(), "oauth_");
        if (FALSE === $this->_tmpDb) {
            throw new Exception("unable to generate temporary file for database");
        }
        $dsn = "sqlite:" . $this->_tmpDb;

        // load default config
        $this->_config = new Config(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini.defaults");

        $this->_config->setValue("accessTokenExpiry", 5);

        // override DB config in memory only
        $this->_config->setValue("storageBackend", "PdoOAuthStorage");
        $this->_config->setSectionValue("PdoOAuthStorage", "dsn", $dsn);

#        $this->_config->setSectionValue("DummyResourceOwner", "resourceOwnerEntitlement") = array ("foo" => array("fkooman"));

        // intialize storage
        $storage = new PdoOAuthStorage($this->_config);
        $storage->initDatabase();

        // add some clients
        $uaba = array("id" => "testclient",
                  "name" => "Simple Test Client",
                  "description" => "Client for unit testing",
                  "secret" => NULL,
                  "icon" => NULL,
                  "allowed_scope" => "read",
                  "contact_email" => "foo@example.org",
                  "redirect_uri" => "http://localhost/php-oauth/unit/test.html",
                  "type" => "user_agent_based_application");

        $wa = array ("id" => "testcodeclient",
                  "name" => "Simple Test Client for Authorization Code Profile",
                  "description" => "Client for unit testing",
                  "secret" => "abcdef",
                  "icon" => NULL,
                  "allowed_scope" => "read",
                  "contact_email" => NULL,
                  "redirect_uri" => "http://localhost/php-oauth/unit/test.html",
                  "type" => "web_application");
        $storage->addClient($uaba);
        $storage->addClient($wa);
    }

    public function tearDown()
    {
        unlink($this->_tmpDb);
    }

    public function testNop()
    {
    }

}
