<?php
require_once "lib/Config.php";
require_once "lib/OAuth/PdoOAuthStorage.php";

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

$storage = new PdoOAuthStorage($config);
$storage->initDatabase();
$storage->updateDatabase();

$redirectUri = ($argc !== 2) ? "http://localhost/html-manage-oauth/index.html" : $argv[1];

if(FALSE === $storage->getClient("manage")) {
    $data = array("id" => "manage",
                  "name" => "Management Client",
                  "description" => "Web application to manage Approvals and OAuth client registrations.",
                  "secret" => NULL,
                  "redirect_uri" => $redirectUri,
                  "type" => "user_agent_based_application");
    $storage->addClient($data);
}
