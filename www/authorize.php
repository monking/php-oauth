<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib");
$c->register();

use \Tuxed\Http\HttpResponse as HttpResponse;
use \Tuxed\Config as Config;
use \Tuxed\Http\IncomingHttpRequest as IncomingHttpRequest;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\OAuth\AuthorizationServer as AuthorizationServer;
use \Tuxed\OAuth\ResourceOwnerException as ResourceOwnerException;
use \Tuxed\OAuth\ClientException as ClientException;
use \Tuxed\Logger as Logger;
use \Tuxed\OAuth\AuthorizeResult as AuthorizeResult;

$logger = NULL;
$request = NULL;
$response = NULL;

try { 
    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $response = new HttpResponse();

    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $logger->logDebug($request);

    $authMech = '\\Tuxed\OAuth\\' . $config->getValue('authenticationMechanism');
    $resourceOwner = new $authMech($config);

    $oauthStorageBackend = '\\Tuxed\OAuth\\' . $config->getValue('storageBackend');
    $storage = new $oauthStorageBackend($config);

    $resourceOwner->setHint($request->getQueryParameter("user_address"));

    $authorizationServer = new AuthorizationServer($storage, $config);

    switch($request->getRequestMethod()) {
        case "GET":
                $result = $authorizationServer->authorize($resourceOwner, $request->getQueryParameters());
                if(AuthorizeResult::ASK_APPROVAL === $result->getAction()) {
                    $response->setHeader("X-Frame-Options", "deny");
                    ob_start();
                    require "../templates" . DIRECTORY_SEPARATOR . "askAuthorization.php";
                    $response->setContent(ob_get_clean());
                } else if (AuthorizeResult::REDIRECT === $result->getAction()) {
                    $response->setStatusCode(302);
                    $response->setHeader("Location", $result->getRedirectUri()->getUri());
                } else {
                    throw new Exception("invalid authorize result");
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
            if(AuthorizeResult::REDIRECT !== $result->getAction()) {
                throw new ResourceOwnerException("approval not found");
            }
            $response->setStatusCode(302);
            $response->setHeader("Location", $result->getRedirectUri()->getUri());
            break;

        default:
            // method not allowed
            $response->setStatusCode(405);
            $response->setHeader("Allow", "GET, POST");
            break;
    }

} catch (ClientException $e) { 
    // tell the client about the error
    $client = $e->getClient();
    $separator = ($client->type === "user_agent_based_application") ? "#" : "?";
    $parameters = array("error" => $e->getMessage(), "error_description" => $e->getDescription());
    if(NULL !== $e->getState()) {
        $parameters['state'] = $e->getState();
    }
    $response->setStatusCode(302);
    $response->setHeader("Location", $client->redirect_uri . $separator . http_build_query($parameters));
    if(NULL !== $logger) {
        $logger->logFatal($e->getLogMessage(TRUE) . PHP_EOL . $request . PHP_EOL . $response);
    }
} catch (ResourceOwnerException $e) {
    // tell resource owner about the error (through browser)
    $response->setStatusCode(400);
    ob_start();
    require "../templates" . DIRECTORY_SEPARATOR . "errorPage.php";
    $response->setContent(ob_get_clean());
    if(NULL !== $logger) {
        $logger->logFatal($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
    }
} catch (Exception $e) {
    // internal server error, inform resource owner through browser
    $response->setStatusCode(500);
    ob_start();
    require "../templates" . DIRECTORY_SEPARATOR . "errorPage.php";
    $response->setContent(ob_get_clean());
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
