<?php

require_once "lib/SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\OAuth\PdoOAuthStorage as PdoOAuthStorage;
use \Tuxed\OAuth\ClientRegistration as ClientRegistration;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

$storage = new PdoOAuthStorage($config);
$storage->initDatabase();

if($argc !== 2) {
        echo "ERROR: specify manifest file or URL to parse" . PHP_EOL;
        die();
}

$manifestFile = $argv[1];
$fileContents = file_get_contents($manifestFile);
$data = json_decode($fileContents, TRUE);
if(NULL === $data || !is_array($data)) {
        echo "ERROR: manifest seems to be in wrong format" . PHP_EOL;
        die();
}

foreach($data as $d) {
        // go over all app entries
        if(FALSE === $storage->getClient($d['key'])) {
                echo "Adding '" . $d['name'] . "'..." . PHP_EOL;
                $x = array (
                        "id" => $d['key'],
                        "name" => $d['name'],
                        "description" => $d['description'],
                        "secret" => NULL,
                        "type" => "user_agent_based_application",
                        "icon" => $d['icons']['128'],
			            "allowed_scope" => implode(" ", $d['permissions']),
                        "redirect_uri" => $d['app']['launch']['web_url'],
                );
                $y = ClientRegistration::fromArray($x);
                $storage->addClient($y->getClientAsArray());
        }
}

?>
