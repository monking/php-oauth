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

    if(!$config->getSectionValue("Api", "enableApi")) {
        throw new ApiException("forbidden","api disabled");
    }

    $oauthStorageBackend = $config->getValue('storageBackend');
    require_once "../lib/OAuth/$oauthStorageBackend.php";
    $storage = new $oauthStorageBackend($config);

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    $rs = new ResourceServer($storage, $config);
    if($request->headerExists("HTTP_AUTHORIZATION")) { 
            
        $token = $rs->verify($request->getHeader("HTTP_AUTHORIZATION"));

        // verify the scope permissions
        if(in_array($request->getCollection(), array ("applications", "authorizations"))) {
            $grantedScope = explode(" ", $token->scope);
            if(!in_array($request->getCollection(), $grantedScope)) {
                throw new VerifyException("insufficient_scope", "no permission for this call with granted scope");
            }
        }

        // verify the entitlement
        $requireEntitlement = $config->getSectionValue("Api", "requireEntitlement");
        if(!array_key_exists($request->getCollection(), $requireEntitlement)) {
            // if the collection entitlement was not explicitely mentioned in the 
            // configuration file, deny access
            throw new ApiException("forbidden", "entitlement configuration missing for this api call");
        }
        if($requireEntitlement[$request->getCollection()]) {
            $grantedEntitlement = explode(" ", $token->resource_owner_entitlement);
            if(!in_array($request->getCollection(), $grantedEntitlement)) {
                throw new ApiException("forbidden", "not entitled to use this api call");
            }
        }

        if($request->matchRest("GET", "resource_owner", "id")) {
            $response->setContent(json_encode(array("id" => $token->resource_owner_id)));
        } else if($request->matchRest("POST", "authorizations", FALSE)) {
            $data = json_decode($request->getContent(), TRUE);
            if(NULL === $data || !is_array($data) || !array_key_exists("client_id", $data) || !array_key_exists("scope", $data)) {
                throw new ApiException("invalid_request", "missing required parameters");
            }
            // check to see if an authorization for this client/resource_owner already exists
            if(FALSE === $storage->getApproval($data['client_id'], $token->resource_owner_id)) {
                if(FALSE === $storage->addApproval($data['client_id'], $token->resource_owner_id, $data['scope'])) {
                    throw new ApiException("invalid_request", "unable to add authorization");
                }
            } else {
                throw new ApiException("invalid_request", "authorization already exists for this client and resource owner");
            }
            $response->setStatusCode(201);
        } else if($request->matchRest("GET", "authorizations", TRUE)) {
            $data = $storage->getApproval($request->getResource(), $token->resource_owner_id);
            if(FALSE === $data) {
                throw new ApiException("not_found", "the resource you are trying to retrieve does not exist");
            }
            $response->setContent(json_encode($data));      
        } else if($request->matchRest("DELETE", "authorizations", TRUE)) {
            if(FALSE === $storage->deleteApproval($request->getResource(), $token->resource_owner_id)) {
                throw new ApiException("not_found", "the resource you are trying to delete does not exist");
            }
        } else if($request->matchRest("GET", "authorizations", FALSE)) {
            $data = $storage->getApprovals($token->resource_owner_id);
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
            $data = json_decode($request->getContent(), TRUE);
            if(NULL === $data || !is_array($data) || 
                    !array_key_exists("id", $data) || 
                    !array_key_exists("name", $data) ||
                    !array_key_exists("description", $data) ||
                    !array_key_exists("secret", $data) ||
                    !array_key_exists("type", $data) ||
                    !array_key_exists("icon", $data) ||
                    !array_key_exists("allowed_scope", $data) ||
                    !array_key_exists("redirect_uri", $data)) {
                throw new ApiException("invalid_request", "missing required parameters");
            }
            // check to see if an application with this id already exists
            if(FALSE === $storage->getClient($data['id'])) {
                if(FALSE === $storage->addClient($data)) {
                    throw new ApiException("invalid_request", "unable to add application");
                }
            } else {
                throw new ApiException("invalid_request", "application already exists");
            }
            $response->setStatusCode(201);
        } else if($request->matchRest("PUT", "applications", TRUE)) {
            $data = json_decode($request->getContent(), TRUE);
            if(NULL === $data || !is_array($data) || 
                    !array_key_exists("name", $data) ||
                    !array_key_exists("description", $data) ||
                    !array_key_exists("secret", $data) ||
                    !array_key_exists("type", $data) ||
                    !array_key_exists("icon", $data) ||
                    !array_key_exists("allowed_scope", $data) ||
                    !array_key_exists("redirect_uri", $data)) {
                throw new ApiException("invalid_request", "missing required parameters");
            }
            if(FALSE === $storage->updateClient($request->getResource(), $data)) {
                throw new ApiException("invalid_request", "unable to update application");
            }
        } else {
            throw new ApiException("invalid_request", "unsupported collection or resource request");
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
