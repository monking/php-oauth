<?php

require_once "../lib/Config.php";
require_once "../lib/OAuth/AuthorizationServer.php";
require_once "../lib/Http/Uri.php";
require_once "../lib/Http/HttpRequest.php";
require_once "../lib/Http/HttpResponse.php";
require_once "../lib/Http/IncomingHttpRequest.php";

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

$authMech = $config->getValue('authenticationMechanism');
require_once "../lib/OAuth/$authMech.php";
$resourceOwner = new $authMech($config);

$oauthStorageBackend = $config->getValue('storageBackend');
require_once "../lib/OAuth/$oauthStorageBackend.php";
$storage = new $oauthStorageBackend($config);

$storage->storeResourceOwner($resourceOwner->getResourceOwnerId(), $resourceOwner->getResourceOwnerDisplayName());
$resourceOwner->setHint(AuthorizationServer::getParameter($_GET, 'user_address'));

$authorizationServer = new AuthorizationServer($storage, $config);

$incomingRequest = new IncomingHttpRequest();
$request = $incomingRequest->getRequest();
$response = new HttpResponse();

switch($request->getRequestMethod()) {
    case "GET":
        try { 
            $result = $authorizationServer->authorize($resourceOwner, $_GET);

            // we know that all request parameters we used below are acceptable because they were verified by the authorize method.
            // Do something with case where no scope is requested!
            if($result['action'] === 'ask_approval') {
                // prevent loading the authorization window in an iframe
                $response->setHeader("X-Frame-Options", "deny");

                $client = $result['client'];

                $templateData = array (
                    'clientId' => $client->id,
                    'clientName' => $client->name,
                    'clientDescription' => $client->description,
                    'clientRedirectUri' => $client->redirect_uri,
                    'scope' => AuthorizationServer::normalizeScope($request->getQueryParameter("scope"), TRUE), 
                    'serviceName' => $config->getValue('serviceName'),
                    'serviceResources' => $config->getValue('serviceResources'),
                    'allowFilter' => $config->getValue('allowResourceOwnerScopeFiltering')
                );
                extract($templateData);
                ob_start();
                require "../templates" . DIRECTORY_SEPARATOR . "askAuthorization.php";
                echo ob_get_clean();

            } else {
                // approval already given?! there can also be some error here!
                $response->setStatusCode(302);
                $response->setHeader("Location", $result['url']);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        break;

    case "POST";
        // CSRF protection, check the referrer, it should be equal to the 
        // request URI
        $fullRequestUri = $request->getRequestUri()->getUri();
        $referrerUri = $request->getHeader("HTTP_REFERER");

        if($fullRequestUri !== $referrerUri) {
            throw new ResourceOwnerException("csrf protection triggered, referrer does not match request uri");
        }
        $result = $authorizationServer->approve($resourceOwner, $request->getQueryParameters(), $request->getPostParameters());
        $response->setStatusCode(302);
        $response->setHeader("Location", $result['url']);
        break;

    default:
        // method not allowed
        $response->setStatusCode(405);
        $response->setHeader("Allow", "GET, POST");
        break;
}

$response->sendResponse();

?>
