<?php

require_once "../lib/OAuth/ApiException.php";
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
            if(NULL === $data || !is_array($data) || !array_key_exists("client_id", $data) || !array_key_exists("scope", $data)) {
                throw new ApiException("invalid_request", "missing required parameters");
            }
            if(FALSE === $storage->addApproval($data['client_id'], $token->resource_owner_id, $data['scope'])) {
                throw new ApiException("invalid_request", "authorization not added");
            }
            $response->setStatusCode(201);
        } else if($restInfo->match("GET", "authorizations", TRUE)) {
            $data = $storage->getApproval($restInfo->getResource(), $token->resource_owner_id);
            if(FALSE === $data) {
                throw new ApiException("not_found", "the resource you are trying to retrieve does not exist");
            }
            $response->setContent(json_encode($data));      
        } else if($restInfo->match("DELETE", "authorizations", TRUE)) {
            if(FALSE === $storage->deleteApproval($restInfo->getResource(), $token->resource_owner_id)) {
                throw new ApiException("not_found", "the resource you are trying to delete does not exist");
            }
        } else if($restInfo->match("GET", "authorizations", FALSE)) {
            $data = $storage->getApprovals($token->resource_owner_id);
            $response->setContent(json_encode($data));      
        } else {
            throw new ApiException("invalid_request", "unsupported collection or resource request");

            // unsupported call
            //$response->setStatusCode(405);
            //$response->setHeader("Allow", "GET,POST,DELETE");
            //$response->setContent(json_encode(array("error" => "Method Not Allowed")));
        }
    } else {
        $response->setStatusCode(401);
        $response->setHeader("WWW-Authenticate", sprintf('Bearer realm="Resource Server"'));
        $response->setContent(json_encode(array("error"=> "not_authorized", "error_description" => "need authorization to access this service")));
    }   
} catch (Exception $e) {
    switch(get_class($e)) {
        case "VerifyException":
            $response->setStatusCode($e->getResponseCode());
            $response->setHeader("WWW-Authenticate", sprintf('Bearer realm="Resource Server",error="%s",error_description="%s"', $e->getMessage(), $e->getDescription()));
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
            break;

        case "ApiException":
            $response->setStatusCode($e->getResponseCode());
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
            break;

        default:
            // any other error thrown by any of the modules, assume internal server error
            $response->setStatusCode(500);
            $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
            break;
    }

}

$response->sendResponse();

?>
