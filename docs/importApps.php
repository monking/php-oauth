<?php

require_once "lib/SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\OAuth\PdoOAuthStorage as PdoOAuthStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

$storage = new PdoOAuthStorage($config);
$storage->initDatabase();

if($argc !== 2) {
        echo "ERROR: specify manifest file or URL to parse" . PHP_EOL;
        die();
}

$manifestFile = $argv[1];
#if(!file_exists($manifestFile) || !is_file($manifestFile) || !is_readable($manifestFile)) {
#        echo "unable to read manifest file." . PHP_EOL;
#        die();
#}

$fileContents = file_get_contents($manifestFile);
$data = json_decode($fileContents, TRUE);
if(NULL === $data || !is_array($data)) {
        echo "ERROR: manifest seems to be in wrong format" . PHP_EOL;
        die();
}

foreach($data as $d) {
        // go over all app entries
        if(FALSE === $storage->getClient($d['key'])) {
                echo "Adding " . $d['name'] . "..." . PHP_EOL;
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
                $storage->addClient($x);
        }
}

?>
