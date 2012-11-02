<?php

require_once "../lib/SplClassLoader.php";
$c1 = new SplClassLoader("RestService", "../extlib/php-rest-service/lib");
$c1->register();
$c2 =  new SplClassLoader("OAuth", "../lib");
$c2->register();

use \RestService\Http\HttpResponse as HttpResponse;
use \RestService\Utils\Config as Config;
use \RestService\Http\IncomingHttpRequest as IncomingHttpRequest;
use \RestService\Http\HttpRequest as HttpRequest;
use \OAuth\Token as Token;
use \RestService\Utils\Logger as Logger;

$logger = NULL;
$request = NULL;
$response = NULL;

try {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $t = new Token($config, $logger);
    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $response = $t->handleRequest($request);
} catch (Exception $e) {
    $response = new HttpResponse();
    $response->setStatusCode(500);
    $response->setHeader("Content-Type", "application/json");
    $response->setContent(json_encode(array("error" => $e->getMessage())));
    if (NULL !== $logger) {
        $logger->logFatal($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
    }
}

if (NULL !== $logger) {
    $logger->logDebug($request);
}
if (NULL !== $logger) {
    $logger->logDebug($response);
}
if (NULL !== $response) {
    $response->sendResponse();
}
