<?php

require_once "../lib/OAuth/ApiException.php";
require_once "../lib/Config.php";
require_once "../lib/Http/Uri.php";
require_once "../lib/Http/HttpRequest.php";
require_once "../lib/Http/HttpResponse.php";
require_once "../lib/Http/IncomingHttpRequest.php";
require_once "../lib/OAuth/Scope.php";
require_once "../lib/OAuth/Client.php";
require_once "../lib/OAuth/ResourceServer.php";

$response = new HttpResponse();
$response->setHeader("Content-Type", "application/json");

try { 
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

    if(!$config->getSectionValue("Api", "enableApi")) {
        throw new ApiException("forbidden","api disabled");
    }

    $oauthStorageBackend = $config->getValue('storageBackend');
    require_once "../lib/OAuth/$oauthStorageBackend.php";
    $storage = new $oauthStorageBackend($config);

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    $rs = new ResourceServer($storage, $config);
    
    $authorizationHeader = $request->getHeader("HTTP_AUTHORIZATION");
    if(NULL === $authorizationHeader) {
        throw new VerifyException("invalid_token", "no token provided");
    }
    $rs->verifyBearerToken($authorizationHeader);

    // verify the scope permissions
    if(in_array($request->getCollection(), array ("applications", "authorizations"))) {
        $rs->requireScope($request->getCollection());
    }

    // verify the entitlement, to manage clients one needs to have the 
    // "applications" entitlement
    if(!$config->getSectionValue("Api", "disableEntitlementEnforcement")) {
        if(in_array($request->getCollection(), array ("applications"))) {
            $rs->requireEntitlement($request->getCollection());
        }
    }

    if($request->matchRest("GET", "resource_owner", "id")) {
        $response->setContent(json_encode(array("id" => $rs->getResourceOwnerId())));
    } else if($request->matchRest("POST", "authorizations", FALSE)) {
        $data = json_decode($request->getContent(), TRUE);
        if(NULL === $data || !is_array($data) || !array_key_exists("client_id", $data) || !array_key_exists("scope", $data)) {
            throw new ApiException("invalid_request", "missing required parameters");
        }
        // check to see if an authorization for this client/resource_owner already exists
        if(FALSE === $storage->getApproval($data['client_id'], $rs->getResourceOwnerId())) {
            if(FALSE === $storage->addApproval($data['client_id'], $rs->getResourceOwnerId(), $data['scope'])) {
                throw new ApiException("invalid_request", "unable to add authorization");
            }
        } else {
            throw new ApiException("invalid_request", "authorization already exists for this client and resource owner");
        }
        $response->setStatusCode(201);
    } else if($request->matchRest("GET", "authorizations", TRUE)) {
        $data = $storage->getApproval($request->getResource(), $rs->getResourceOwnerId());
        if(FALSE === $data) {
            throw new ApiException("not_found", "the resource you are trying to retrieve does not exist");
        }
        $response->setContent(json_encode($data));      
    } else if($request->matchRest("DELETE", "authorizations", TRUE)) {
        if(FALSE === $storage->deleteApproval($request->getResource(), $rs->getResourceOwnerId())) {
            throw new ApiException("not_found", "the resource you are trying to delete does not exist");
        }
    } else if($request->matchRest("GET", "authorizations", FALSE)) {
        $data = $storage->getApprovals($rs->getResourceOwnerId());
        $response->setContent(json_encode($data));      
    } else if($request->matchRest("GET", "applications", FALSE)) {
        $data = $storage->getClients();
        $response->setContent(json_encode($data)); 
    } else if($request->matchRest("DELETE", "applications", TRUE)) {
        if(FALSE === $storage->deleteClient($request->getResource())) {
            throw new ApiException("not_found", "the resource you are trying to delete does not exist");
        }
    } else if($request->matchRest("GET", "applications", TRUE)) {
        $data = $storage->getClient($request->getResource());
        if(FALSE === $data) {
            throw new ApiException("not_found", "the resource you are trying to retrieve does not exist");
        }
        $response->setContent(json_encode($data));
    } else if($request->matchRest("POST", "applications", FALSE)) {
        try { 
            $client = Client::fromArray(json_decode($request->getContent(), TRUE));
            $data = $client->getClientAsArray();
            // check to see if an application with this id already exists
            if(FALSE === $storage->getClient($data['id'])) {
                if(FALSE === $storage->addClient($data)) {
                    throw new ApiException("invalid_request", "unable to add application");
                }
            } else {
                throw new ApiException("invalid_request", "application already exists");
            }
            $response->setStatusCode(201);
        } catch(ClientException $e) {
            throw new ApiException("invalid_request", $e->getMessage());
        }
    } else if($request->matchRest("PUT", "applications", TRUE)) {
        try {
            $client = Client::fromArray(json_decode($request->getContent(), TRUE));
            $data = $client->getClientAsArray();
            if($data['id'] !== $request->getResource()) {
                throw new ApiException("invalid_request", "resource does not match client id value");
            }
            if(FALSE === $storage->updateClient($request->getResource(), $data)) {
                throw new ApiException("invalid_request", "unable to update application");
            }
        } catch(ClientException $e) {
            throw new ApiException("invalid_request", $e->getMessage());
        }        
    } else {
        throw new ApiException("invalid_request", "unsupported collection or resource request");
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
