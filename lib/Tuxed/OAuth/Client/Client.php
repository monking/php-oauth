<?php

namespace Tuxed\OAuth\Client;

use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\Http\OutgoingHttpRequest as OutgoingHttpRequest;
use \Tuxed\Http\HttpResponse as HttpResponse;
use \Tuxed\Logger as Logger;
use \Tuxed\Config as Config;

class Client {
    private $_config;
    private $_logger;

    private $_incomingHttpRequest;
    private $_redirectUri;
    private $_scope;

    private $_storage;

    public function __construct(Config $c, Logger $l = NULL) {
        $this->_config = $c;
        $this->_logger = $l;
        $this->_incomingHttpRequest = NULL;  
        $this->_redirectUri = NULL;
        $this->_scope = NULL;

        $oauthStorageBackend = '\\Tuxed\OAuth\\Client\\' . $this->_config->getValue('storageBackend');
        $this->_storage = new $oauthStorageBackend($this->_config);
    }

    public function setIncomingHttpRequest(HttpRequest $request) {
        $this->_incomingHttpRequest = $request;
    }

    public function getIncomingHttpRequest() {
        return $this->_incomingHttpRequest;
    }

    public function setRedirectUri($redirectUri) {
        $this->_redirectUri = $redirectUri;
    }

    public function getRedirectUri() {
        return $this->_redirectUri;
    }

    public function setScope($scope) {
        $this->_scope = $scope;
    }

    public function getScope() {
        return $this->_scope;
    }

    public function getAuthorizationCode(HttpRequest $h) {
        if(NULL !== $h->getQueryParameter("code") && NULL !== $h->getQueryParameter("state")) {
            // we have an authorization code!
            $state = $this->_storage->getStateByState($h->getQueryParameter('state'));
            if(FALSE === $state) {
                throw new Exception("state does not match expected value");
            }
            $this->_storage->updateState($state['state'], $state['redirect_uri'], $h->getQueryParameter("code"));
            return $h->getQueryParameter("code");
        }
        $originalRequestUri = $this->_incomingHttpRequest->getRequestUri()->getUri();
        // we don't have an authorization code, request it
        $state = bin2hex(openssl_random_pseudo_bytes(8));
        $this->_storage->storeState($state, $originalRequestUri);

        $parameters = array (
            "client_id" => $this->_config->getValue('clientId'),
            "response_type" => "code",
            "state" => $state
        );
        if(NULL !== $this->_scope) {
            $parameters['scope'] = $this->_scope;
        }
        if(NULL !== $this->_redirectUri) {
            $parameters['redirect_uri'] = $this->_redirectUri;
        }

        $authorizeUri = $this->_config->getValue('authorizeEndpoint') . "?" . http_build_query($parameters);

        $response = new HttpResponse(302);
        $response->setHeader("Location", $authorizeUri);
        return $response;
    }

    public function getAccessTokenFromCode($authorizationCode) {
        // exchange the authorization code for an access token
        $basicAuth = base64_encode($this->_config->getValue("clientId") . ":" . $this->_config->getValue("clientSecret"));

        $h = new HttpRequest($this->_config->getValue("tokenEndpoint"), "POST");
        $postParameters = array();
        $postParameters['code'] = $authorizationCode;
        $postParameters['grant_type'] = "authorization_code";
        if(NULL !== $this->_redirectUri) {
            $postParameters['redirect_uri'] = $this->_redirectUri;
        }
        $h->setPostParameters($postParameters);
        $h->setHeader("Authorization", "Basic $basicAuth");
        return $h;
    }

    public function getAccessTokenFromResponse(HttpResponse $r) {
        $content = $r->getContent();
        $d = base64_decode($content, TRUE);
        return $d['access_token'];
    }

    private function _fetchNewAccessToken($resourceOwnerId) {
        $r = $this->getAuthorizationCode($this->_incomingHttpRequest);
        if($r instanceof HttpResponse) {
            $r->sendResponse();
            exit;
        }
        $h = $this->getAccessTokenFromCode($r);
        $response = OutgoingHttpRequest::makeRequest($h);
        // FIXME: verify result?
        $j = json_decode($response->getContent(), TRUE);
        $accessToken = $j['access_token'];

        $this->_storage->storeAccessToken($accessToken, time(), $resourceOwnerId, $this->_scope, $j['expires_in']);
      
        // we have stored the access token now, remove the code and state from the
        // URI and redirect to original request URI
        $state = $this->_storage->getStateByCode($r);
        $this->_storage->deleteState($state['state']);
        $resp = new HttpResponse(302);
        // FIXME: if we just require the redirectUri we are always going to be fine and we don't need
        // this database hack with redirectUri and code, we only need to remember the state...
        $resp->setHeader("Location", $state['redirect_uri']);
        $resp->sendResponse();
        exit;
    }

    public function makeRequest($resourceOwnerId, HttpRequest $request) {
        $accessToken = NULL;
        $a = $this->_storage->getAccessToken($resourceOwnerId, $this->_scope);
        if(FALSE === $a) {
            // no token found, fetch new one
            $this->_fetchNewAccessToken($resourceOwnerId);
        }
        if(time() > $a['issue_time'] + $a['expires_in']) {
            // expired token, fetch new one
            $this->_storage->deleteAccessToken($a['access_token']);
            $this->_fetchNewAccessToken($resourceOwnerId);
        }
        // use existing token
        $request->setHeader("Authorization", "Bearer " . $a['access_token']);
        $response = OutgoingHttpRequest::makeRequest($request);
        if(401 === $response->getStatusCode()) {
            $content = json_decode($response->getContent(), TRUE);
            if("invalid_token" === $content['error']) {
                // token is invalid or expired
                // we should NOT try this indefinetly I guess...
                $this->_storage->deleteAccessToken($a['access_token']);
                $this->_fetchNewAccessToken($resourceOwnerId);
            }
        }
        return $response;
    }

}
