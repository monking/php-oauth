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

$response = new HttpResponse();
$response->setHeader("Content-Type", "application/json");

try { 
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

    $oauthStorageBackend = '\\Tuxed\OAuth\\' . $config->getValue('storageBackend');
    $storage = new $oauthStorageBackend($config);

    $authorizationServer = new AuthorizationServer($storage, $config);

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    switch($request->getRequestMethod()) {

        case "POST":
            $result = $authorizationServer->token($request->getPostParameters(), $request->getBasicAuthUser(), $request->getBasicAuthPass());
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Cache-Control', 'no-store');
            $response->setHeader('Pragma', 'no-cache');
            $response->setContent(json_encode($result));
            break;

        default: 
            // method not allowed
            $response->setStatusCode(405);
            $response->setHeader("Allow", "POST");
            break;
    }
} catch (Exception $e) {
    switch(get_class($e)) {
        case "Tuxed\\OAuth\\TokenException":
            if($e->getResponseCode() === 401) {
                $response->setHeader("WWW-Authenticate", 'Basic realm="OAuth Server"');
            }
            $response->setStatusCode($e->getResponseCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Cache-Control', 'no-store');
            $response->setHeader('Pragma', 'no-cache');
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
            break;

        default:
            $response->setStatusCode(500);
            $response->setContent(json_encode(array("error" => $e->getMessage())));
    }
}

$response->sendResponse();

?>
