<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\Logger as Logger;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\Http\IncomingHttpRequest as IncomingHttpRequest;
use \Tuxed\OAuth\Authorize as Authorize;
use \Tuxed\Http\HttpResponse as HttpResponse;

$logger = NULL;
$request = NULL;
$response = NULL;

try { 
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $a = new Authorize($config, $logger);
    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $response = $a->handleRequest($request);

} catch (Exception $e) {
    // internal server error, inform resource owner through browser
    $response = new HttpResponse();
    $response->setStatusCode(500);
    ob_start();
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "errorPage.php";
    $response->setContent(ob_get_clean());
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
