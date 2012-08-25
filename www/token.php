<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib");
$c->register();

use \Tuxed\Http\HttpResponse as HttpResponse;
use \Tuxed\Config as Config;
use \Tuxed\OAuth\AuthorizationServer as AuthorizationServer;
use \Tuxed\Http\IncomingHttpRequest as IncomingHttpRequest;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\OAuth\TokenException as TokenException;
use \Tuxed\Logger as Logger;

$logger = NULL;
$request = NULL;
$response = NULL;

try { 
    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $response = new HttpResponse();
    $response->setHeader("Content-Type", "application/json");

    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $logger->logDebug($request);

    $oauthStorageBackend = '\\Tuxed\OAuth\\' . $config->getValue('storageBackend');
    $storage = new $oauthStorageBackend($config);

    $authorizationServer = new AuthorizationServer($storage, $config);
   
    if("POST" !== $request->getRequestMethod()) {
        // method not allowed
        $response->setStatusCode(405);
        $response->setHeader("Allow", "POST");
    } else {
        $result = $authorizationServer->token($request->getPostParameters(), $request->getBasicAuthUser(), $request->getBasicAuthPass());
        $response->setHeader('Content-Type', 'application/json');
        $response->setHeader('Cache-Control', 'no-store');
        $response->setHeader('Pragma', 'no-cache');
        $response->setContent(json_encode($result));
    }
} catch (TokenException $e) {
    if($e->getResponseCode() === 401) {
        $response->setHeader("WWW-Authenticate", 'Basic realm="OAuth Server"');
    }
    $response->setStatusCode($e->getResponseCode());
    $response->setHeader('Content-Type', 'application/json');
    $response->setHeader('Cache-Control', 'no-store');
    $response->setHeader('Pragma', 'no-cache');
    $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription()))); 
    if(NULL !== $logger) {
        $logger->logFatal($e->getLogMessage(TRUE) . PHP_EOL . $request . PHP_EOL . $response);
    }
} catch (Exception $e) {
    $response->setStatusCode(500);
    $response->setContent(json_encode(array("error" => $e->getMessage())));
    if(NULL !== $logger) {
        $logger->logFatal($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
    }
}

if(NULL !== $logger) {
    $logger->logDebug($response);
}
if(NULL !== $response) {
    $response->sendResponse();
}
