<?php

require_once "lib/SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\OAuth\PdoOAuthStorage as PdoOAuthStorage;
use \Tuxed\OAuth\ClientRegistration as ClientRegistration;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

$storage = new PdoOAuthStorage($config);
$storage->initDatabase();

$registration = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "registration.json"), TRUE);

foreach($registration as $r) {
    if(FALSE === $storage->getClient($r['id'])) {    
        // does not exist yet, install
        echo "Adding '" . $r['name'] . "'..." . PHP_EOL;
        $storage->addClient($r);
    }
}
