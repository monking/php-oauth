<?php

require_once "../lib/Config.php";
require_once "../lib/Http/Uri.php";
require_once "../lib/Http/HttpRequest.php";
require_once "../lib/Http/HttpResponse.php";
require_once "../lib/Http/IncomingHttpRequest.php";
require_once "../lib/OAuth/ResourceServer.php";

$response = new HttpResponse();
$response->setHeader("Content-Type", "application/json");

try { 
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

    $oauthStorageBackend = $config->getValue('storageBackend');
    require_once "../lib/OAuth/$oauthStorageBackend.php";
    $storage = new $oauthStorageBackend($config);

    $incomingRequest = new IncomingHttpRequest();
    $request = $incomingRequest->getRequest();

    $restInfo = $request->getRestInfo();
    
    $rs = new ResourceServer($storage, $config);
    if($request->headerExists("HTTP_AUTHORIZATION")) { 
            
        $token = $rs->verify($request->getHeader("HTTP_AUTHORIZATION"));

        if($restInfo->match("POST", "authorizations", FALSE)) {
            $data = json_decode($request->getContent(), TRUE);
            $storage->addApproval($data['client_id'], $token->resource_owner_id, $data['scope']);
            $response->setStatusCode(201);
        } else if($restInfo->match("GET", "authorizations", TRUE)) {
            $data = $storage->getApproval($restInfo->getResource(), $token->resource_owner_id);
            $response->setContent(json_encode($data));      
        } else if($restInfo->match("DELETE", "authorizations", TRUE)) {
            $data = $storage->deleteApproval($restInfo->getResource(), $token->resource_owner_id);
        } else if($restInfo->match("GET", "authorizations", FALSE)) {
            $data = $storage->getApprovals($token->resource_owner_id);
            $response->setContent(json_encode($data));      
        } else {
            // unsupported call
            $response->setStatusCode(405);
            $response->setHeader("Allow", "GET,POST,DELETE");
            $response->setContent(json_encode(array("error" => "Method Not Allowed")));
        }
    } else {
        $response->setStatusCode(401);
        $response->setHeader("Content-Type", "text/plain");
        $response->setHeader("WWW-Authenticate", sprintf('Bearer realm="Resource Server"'));
        $response->setContent("Unauthorized");
    }   
} catch (Exception $e) {
    switch(get_class($e)) {
        case "VerifyException":
            $response->setStatusCode($e->getResponseCode());
            $response->setHeader("WWW-Authenticate", sprintf('Bearer realm="Resource Server",error="%s",error_description="%s"', $e->getMessage(), $e->getDescription()));
            break;

        default:
            $response->setStatusCode(500);
            $response->setContent(json_encode(array("error" => $e->getMessage())));
            break;
    }

}

$response->sendResponse();

?>
