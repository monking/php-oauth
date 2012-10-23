<?php

namespace Tuxed\OAuth;

use \Tuxed\Config as Config;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\Http\HttpResponse as HttpResponse;
use \Tuxed\Logger as Logger;

class TokenInfo
{
    private $_config;
    private $_logger;

    private $_as;

    public function __construct(Config $c, Logger $l = NULL)
    {
        $this->_config = $c;
        $this->_logger = $l;

        $oauthStorageBackend = '\\Tuxed\OAuth\\' . $this->_config->getValue('storageBackend');
        $storage = new $oauthStorageBackend($this->_config);

        $this->_as = new AuthorizationServer($storage, $this->_config);
    }

    public function handleRequest(HttpRequest $request)
    {
        $response = new HttpResponse();
        try {
            if ("GET" !== $request->getRequestMethod()) {
                // method not allowed
                $response->setStatusCode(405);
                $response->setHeader("Allow", "GET");
            } else {
                $result = $this->_as->tokenInfo($request->getQueryParameters());
                $response->setContentType("application/json");
                $response->setHeader('Content-Type', 'application/json');
                $response->setHeader('Cache-Control', 'no-store');
                $response->setHeader('Pragma', 'no-cache');

                $tokenInfo = array (
                    "audience" => $result->client_id, 
                    //"client_id" => $result->client_id, 
                    "user_id" => $result->resource_owner_id, 
                    //"resource_owner_id" => $result->resource_owner_id, 
                    "scope" => $result->scope, 
                    "expires_in" => $result->issue_time + $result->expires_in - time(),
                    "attributes" => $result->resource_owner_attributes);
                $response->setContent(json_encode($tokenInfo));
            }
        } catch (TokenInfoException $e) {
            $response->setStatusCode(400);
            $response->setContentType("application/json");
            $response->setHeader('Cache-Control', 'no-store');
            $response->setHeader('Pragma', 'no-cache');
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
            if (NULL !== $this->_logger) {
                $this->_logger->logFatal($e->getLogMessage(TRUE) . PHP_EOL . $request . PHP_EOL . $response);
            }
        }

        return $response;
    }

}
