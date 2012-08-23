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

$response = new HttpResponse();

try { 
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");

    $authMech = '\\Tuxed\OAuth\\' . $config->getValue('authenticationMechanism');
    $resourceOwner = new $authMech($config);

    $oauthStorageBackend = '\\Tuxed\OAuth\\' . $config->getValue('storageBackend');
    $storage = new $oauthStorageBackend($config);

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    $resourceOwner->setHint($request->getQueryParameter("user_address"));

    $authorizationServer = new AuthorizationServer($storage, $config);

    switch($request->getRequestMethod()) {
        case "GET":
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
                        'scope' => (NULL !== $request->getQueryParameter("scope")) ? explode(" ", $request->getQueryParameter("scope")) : NULL, 
                        'serviceName' => $config->getValue('serviceName'),
                        'serviceResources' => $config->getValue('serviceResources'),
                        'allowFilter' => $config->getValue('allowResourceOwnerScopeFiltering')
                    );
                    extract($templateData);
                    ob_start();
                    require "../templates" . DIRECTORY_SEPARATOR . "askAuthorization.php";
                    $response->setContent(ob_get_clean());
                } else {
                    // approval already given?! there can also be some error here!
                    $response->setStatusCode(302);
                    $response->setHeader("Location", $result['url']);
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

//} catch (ResourceOwnerException $e) {
    // FIXME: implement a different error for this, probably with more details!

} catch (Exception $e) {
    // tell resource owner about the error (through browser)
    $templateData = array ("error" => $e->getMessage());
    extract($templateData);
    ob_start();
    require "../templates" . DIRECTORY_SEPARATOR . "errorPage.php";
    $response->setStatusCode(500);
    $response->setContent(ob_get_clean());
}

$response->sendResponse();

?>
