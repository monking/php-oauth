<?php

namespace Tuxed\OAuth;

use \Tuxed\Config as Config;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\Http\HttpResponse as HttpResponse;
use \Tuxed\Logger as Logger;

class Authorize
{
    private $_config;
    private $_logger;

    private $_resourceOwner;
    private $_as;

    public function __construct(Config $c, Logger $l = NULL)
    {
        $this->_config = $c;
        $this->_logger = $l;

        $authMech = '\\Tuxed\OAuth\\' . $this->_config->getValue('authenticationMechanism');
        $this->_resourceOwner = new $authMech($this->_config);
#        $this->_resourceOwner->setHint($request->getQueryParameter("user_address"));

        $oauthStorageBackend = '\\Tuxed\OAuth\\' . $this->_config->getValue('storageBackend');
        $storage = new $oauthStorageBackend($this->_config);

        $this->_as = new AuthorizationServer($storage, $this->_config);
    }

    public function handleRequest(HttpRequest $request)
    {
        $response = new HttpResponse();
        try {
            switch ($request->getRequestMethod()) {
                case "GET":
                        $result = $this->_as->authorize($this->_resourceOwner, $request->getQueryParameters());
                        if (AuthorizeResult::ASK_APPROVAL === $result->getAction()) {
                            $response->setHeader("X-Frame-Options", "deny");
                            $tplData = array(
                                "config" => $this->_config,
                                "client" => $result->getClient(),
                                "scope" => $result->getScope(),
                                "sslEnabled" => "https" === $request->getRequestUri()->getScheme(),
                            );
                            extract($tplData);
                            ob_start();
                            require dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "askAuthorization.php";
                            $response->setContent(ob_get_clean());
                        } elseif (AuthorizeResult::REDIRECT === $result->getAction()) {
                            $response->setStatusCode(302);
                            $response->setHeader("Location", $result->getRedirectUri()->getUri());
                        } else {
                            // should never happen...
                            throw new \Exception("invalid authorize result");
                        }
                    break;

                case "POST";
                    // CSRF protection, check the referrer, it should be equal to the
                    // request URI
                    $fullRequestUri = $request->getRequestUri()->getUri();
                    $referrerUri = $request->getHeader("HTTP_REFERER");

                    if ($fullRequestUri !== $referrerUri) {
                        throw new ResourceOwnerException("csrf protection triggered, referrer does not match request uri");
                    }
                    $result = $this->_as->approve($this->_resourceOwner, $request->getQueryParameters(), $request->getPostParameters());
                    if (AuthorizeResult::REDIRECT !== $result->getAction()) {
                        // FIXME: this is dead code?
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
            if (NULL !== $e->getState()) {
                $parameters['state'] = $e->getState();
            }
            $response->setStatusCode(302);
            $response->setHeader("Location", $client->redirect_uri . $separator . http_build_query($parameters));
            if (NULL !== $this->_logger) {
                $this->_logger->logFatal($e->getLogMessage(TRUE) . PHP_EOL . $request . PHP_EOL . $response);
            }
        } catch (ResourceOwnerException $e) {
            // tell resource owner about the error (through browser)
            $response->setStatusCode(400);
            ob_start();
            require dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "errorPage.php";
            $response->setContent(ob_get_clean());
            if (NULL !== $this->_logger) {
                $this->_logger->logFatal($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
            }
        }

        return $response;
    }

}
