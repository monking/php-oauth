<?php

namespace Tuxed\OAuth;

use \Tuxed\Config as Config;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\Http\HttpResponse as HttpResponse;
use \Tuxed\Logger as Logger;

class Token {
    private $_config;
    private $_logger;

    private $_as;

    public function __construct(Config $c, Logger $l = NULL) {
        $this->_config = $c;
        $this->_logger = $l;

        $oauthStorageBackend = '\\Tuxed\OAuth\\' . $this->_config->getValue('storageBackend');
        $storage = new $oauthStorageBackend($this->_config);

        $this->_as = new AuthorizationServer($storage, $this->_config);
    }

    public function handleRequest(HttpRequest $request) {
        $response = new HttpResponse();
        try { 

            if("POST" !== $request->getRequestMethod()) {
                // method not allowed
                $response->setStatusCode(405);
                $response->setHeader("Allow", "POST");
            } else {
                $result = $this->_as->token($request->getPostParameters(), $request->getBasicAuthUser(), $request->getBasicAuthPass());
                $response->setContentType("application/json");
                $response->setHeader('Content-Type', 'application/json');
                $response->setHeader('Cache-Control', 'no-store');
                $response->setHeader('Pragma', 'no-cache');
                $response->setContent(json_encode($result));
            }

        } catch (TokenException $e) {
            if($e->getResponseCode() === 401) {
                $response->setHeader("WWW-Authenticate", 'Basic realm="OAuth Server"');
            }
            $response->setStatusCode($e->getResponseCode());
            $response->setContentType("application/json");
            $response->setHeader('Cache-Control', 'no-store');
            $response->setHeader('Pragma', 'no-cache');
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription()))); 
            if(NULL !== $this->_logger) {
                $this->_logger->logFatal($e->getLogMessage(TRUE) . PHP_EOL . $request . PHP_EOL . $response);
            }
        }
        return $response;
    }

}
