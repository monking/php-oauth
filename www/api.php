<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib");
$c->register();

#function exception_error_handler($errno, $errstr, $errfile, $errline ) {
#    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
#}
#set_error_handler("exception_error_handler");

use \Tuxed\Http\HttpResponse as HttpResponse;
use \Tuxed\Config as Config;
use \Tuxed\Http\IncomingHttpRequest as IncomingHttpRequest;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\Logger as Logger;
use \Tuxed\OAuth\Api as Api;

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
    if(NULL !== $logger) {
        $logger->logFatal($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
    }
}

if(NULL !== $logger) {
    $logger->logDebug($request);
}
if(NULL !== $logger) {
    $logger->logDebug($response);
}
if(NULL !== $response) {
    $response->sendResponse();
}
