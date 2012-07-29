<?php

require_once "../lib/Config.php";
require_once "../lib/OAuth/AuthorizationServer.php";
require_once "../lib/Http/Uri.php";
require_once "../lib/Http/HttpRequest.php";
require_once "../lib/Http/HttpResponse.php";
require_once "../lib/Http/IncomingHttpRequest.php";

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

$oauthStorageBackend = $config->getValue('storageBackend');
require_once "../lib/OAuth/$oauthStorageBackend.php";
$storage = new $oauthStorageBackend($config);

$authorizationServer = new AuthorizationServer($storage, $config);

$incomingRequest = new IncomingHttpRequest();
$request = $incomingRequest->getRequest();
$response = new HttpResponse();

switch($request->getRequestMethod()) {

    case "POST":
        try {
            $result = $authorizationServer->token($request->getPostParameters(), $request->getBasicAuthUser(), $request->getBasicAuthPass());
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Cache-Control', 'no-store');
            $response->setHeader('Pragma', 'no-cache');
            $response->setContent(json_encode($result));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        break;

    default: 
        // method not allowed
        $response->setStatusCode(405);
        $response->setHeader("Allow", "POST");
        break;
}

$response->sendResponse();

?>
