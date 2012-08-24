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

$response = new HttpResponse();
$response->setHeader("Content-Type", "application/json");

$logger = NULL;

try { 
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");
    $logger = new Logger($config->getValue('serviceName'), $config->getValue('logFile'), $config->getValue('logMail', FALSE));

    $oauthStorageBackend = '\\Tuxed\OAuth\\' . $config->getValue('storageBackend');
    $storage = new $oauthStorageBackend($config);

    $authorizationServer = new AuthorizationServer($storage, $config);

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $logger->logDebug($request->toString());
   
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
    $logger->logFatal($e->getLogMessage(TRUE));
} catch (Exception $e) {
    $response->setStatusCode(500);
    $response->setContent(json_encode(array("error" => $e->getMessage())));
    $logger->logFatal($e->getMessage());
}

if(NULL !== $logger) {
    $logger->logDebug($response->toString());
}
$response->sendResponse();

?>
