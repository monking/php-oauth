<?php
require_once "lib/Config.php";
require_once "lib/OAuth/PdoOAuthStorage.php";

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

$storage = new PdoOAuthStorage($config);
$storage->initDatabase();
$storage->updateDatabase();

$redirectUri = ($argc !== 2) ? "http://localhost/html-manage-oauth/index.html" : $argv[1];

if(FALSE === $storage->getClient("authorization_manager")) {
    $data = array("id" => "authorization_manager",
                  "name" => "Manage Authorizations",
                  "description" => "This application can be used by end users to view and revoke application permissions.",
                          "secret" => NULL,
                  "redirect_uri" => "http://localhost/html-manage-authorizations/index.html",
                  "type" => "user_agent_based_application");
    $storage->addClient($data);
}

if(FALSE === $storage->getClient("application_manager")) {
    $data = array("id" => "application_manager",
                  "name" => "Manage Applications",
                  "description" => "This application can be used by administrators to manage applications.",
                  "secret" => NULL,
                  "redirect_uri" => "http://localhost/html-manage-applications/index.html",
                  "type" => "user_agent_based_application");
    $storage->addClient($data);
}


if(FALSE === $storage->getClient("democlient")) {
    $data = array("id" => "democlient",
                  "name" => "Web Application Profile Demo Client",
                  "description" => "This application can be used to test REST APIs protected by OAuth.",
                  "secret" => 's3cr3t',
                  "redirect_uri" => "http://localhost/php-oauth-demo-client/index.php",
                  "type" => "web_application");
    $storage->addClient($data);
}
?>
