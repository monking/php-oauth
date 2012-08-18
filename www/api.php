<?php

require_once "../lib/OAuth/ApiException.php";
require_once "../lib/Config.php";
require_once "../lib/Http/Uri.php";
require_once "../lib/Http/HttpRequest.php";
require_once "../lib/Http/HttpResponse.php";
require_once "../lib/Http/IncomingHttpRequest.php";
require_once "../lib/OAuth/Scope.php";
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
    $token = $rs->verify($authorizationHeader);

    // verify the scope permissions
    if(in_array($request->getCollection(), array ("applications", "authorizations"))) {
        $grantedScope = explode(" ", $token->scope);
        if(!in_array($request->getCollection(), $grantedScope)) {
            throw new VerifyException("insufficient_scope", "no permission for this call with granted scope");
        }
    }

    // verify the entitlement, to manage clients one needs to have the 
    // "applications" entitlement
    if(!$config->getSectionValue("Api", "disableEntitlementEnforcement")) {
        if(in_array($request->getCollection(), array ("applications"))) {
            $grantedEntitlement = explode(" ", $token->resource_owner_entitlement);
            if(!in_array($request->getCollection(), $grantedEntitlement)) {
                throw new ApiException("forbidden", "not entitled to use this api call");
            }
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
        $data = validateClientData($request->getContent());
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
        $data = validateClientData($request->getContent());
        if($data['id'] !== $request->getResource()) {
            throw new ApiException("invalid_request", "resource does not match client id value");
        }
        if(FALSE === $storage->updateClient($request->getResource(), $data)) {
            throw new ApiException("invalid_request", "unable to update application");
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

// FIXME: deal with PUT and POST both
function validateClientData($d) { 
    $data = json_decode($d, TRUE);
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

    if(empty($data['id'])) {
		    throw new ApiException("invalid_request", "id cannot be empty");
    }

    if(empty($data['name'])) {
		    throw new ApiException("invalid_request", "name cannot be empty");
    }

    // client type should be any of below types
    if(!in_array($data['type'], array ("user_agent_based_application", "web_application", "native_application"))) {
	    throw new ApiException("invalid_request", "unsupported client type");
    }

    // secret cannot be empty when type is "web_application"
    if("web_application" === $data['type'] && empty($data['secret'])) {
        throw new ApiException("invalid_request", "secret should be set for web_application type");
    }

    // icon should be empty, or URL with path
    if(!empty($data['icon'])) { 
        if(FALSE === filter_var($data['icon'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
            throw new ApiException("invalid_request", "icon should be valid URL with path");
        }
    }

    // redirect_uri should be URL
    if(FALSE === filter_var($data['redirect_uri'], FILTER_VALIDATE_URL)) {
        throw new ApiException("invalid_request", "redirect_uri should be valid URL");
    }
    // and it is not allowed to have a fragment (#) in it
    if(NULL !== parse_url($data['redirect_uri'], PHP_URL_FRAGMENT)) {
        throw new ApiException("invalid_request", "redirect_uri cannot contain a fragment");
    }

    // scope should be valid
    try {
        $s = new Scope($data['allowed_scope']);
    } catch (ScopeException $e) {
        throw new ApiException("invalid_request", "scope is invalid");
    }
    return $data;
}


?>
