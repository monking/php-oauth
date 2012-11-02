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
use \RestService\Utils\Logger as Logger;
use \OAuth\Api as Api;

$logger = NULL;
$request = NULL;
$response = NULL;

try {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $a = new Api($config, $logger);
    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $response = $a->handleRequest($request);

} catch (Exception $e) {
    // any other error thrown by any of the modules, assume internal server error
    $response = new HttpResponse();
    $response->setStatusCode(500);
    $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
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
