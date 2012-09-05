<?php

require_once "lib/SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\OAuth\PdoOAuthStorage as PdoOAuthStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

$storage = new PdoOAuthStorage($config);
$storage->initDatabase();

if(FALSE === $storage->getClient("authorization_manager")) {
    $data = array("id" => "authorization_manager",
                  "name" => "Manage Authorizations",
                  "description" => "This application can be used by end users to view and revoke application permissions.",
                  "secret" => NULL,
                  "redirect_uri" => "http://localhost/html-manage-authorizations/index.html",
                  "icon" => NULL,
                  "allowed_scope" => "authorizations",
                  "type" => "user_agent_based_application");
    $storage->addClient($data);
}

if(FALSE === $storage->getClient("application_manager")) {
    $data = array("id" => "application_manager",
                  "name" => "Manage Applications",
                  "description" => "This application can be used by administrators to manage applications.",
                  "secret" => NULL,
                  "redirect_uri" => "http://localhost/html-manage-applications/index.html",
                  "icon" => NULL,
                  "allowed_scope" => "applications",
                  "type" => "user_agent_based_application");
    $storage->addClient($data);
}

if(FALSE === $storage->getClient("remotestorage_portal")) {
    $data = array("id" => "remotestorage_portal",
                  "name" => "remoteStorage Portal",
                  "description" => "This application can be used to install and launch 'Unhosted' applications.",
                  "secret" => NULL,
                  "redirect_uri" => "http://localhost/html-remoteStorage-portal/index.html",
                  "icon" => NULL,
                  "allowed_scope" => "applications authorizations",
                  "type" => "user_agent_based_application");
    $storage->addClient($data);
}

if(FALSE === $storage->getClient("php-oauth-code-client")) {
    $data = array("id" => "php-oauth-code-client",
                  "name" => "Authorization Code Test Client",
                  "description" => "This application can be used to test REST APIs protected by OAuth.",
                  "secret" => 's3cr3t',
                  "redirect_uri" => "http://localhost/php-oauth-code-client/index.php",
                  "icon" => NULL,
                  "allowed_scope" => "read",
                  "type" => "web_application");
    $storage->addClient($data);
}
?>
