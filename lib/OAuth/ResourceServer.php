<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "Config.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "IOAuthStorage.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "VerifyException.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "Scope.php";

class ResourceServer {

    private $_storage;
    private $_c;
    private $_bearerToken;
    private $_grantedEntitlement;

    public function __construct(Config $c = NULL) {
        // it is possible to override the config from the default...
        if(NULL === $c) {
            $this->_c = new Config(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini");
        } else {
            $this->_c = $c;
        }
        $oauthStorageBackend = $this->_c->getValue('storageBackend');
        require_once __DIR__ . DIRECTORY_SEPARATOR . $oauthStorageBackend . ".php";
        $this->_storage = new $oauthStorageBackend($this->_c);
        $this->_bearerToken = NULL;
        $this->_grantedEntitlement = NULL;
    }

    public function verifyAuthorizationHeader($authorizationHeader) {
        // b64token = 1*( ALPHA / DIGIT / "-" / "." / "_" / "~" / "+" / "/" ) *"="
        $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';
        $result = preg_match('|^Bearer (?P<value>' . $b64TokenRegExp . ')$|', $authorizationHeader, $matches);
        if($result === FALSE || $result === 0) {
            throw new VerifyException("invalid_token", "the access token is malformed");
        }
        $accessToken = $matches['value'];
        $token = $this->_storage->getAccessToken($accessToken);
        if(FALSE === $token) {
            throw new VerifyException("invalid_token", "the access token is invalid");
        }
        if(time() > $token->issue_time + $token->expires_in) {
            throw new VerifyException("invalid_token", "the access token expired");
        }
        $this->_bearerToken = $token;

        $entitlement = $this->_storage->getEntitlement($token->resource_owner_id);
        $this->_grantedEntitlement = $entitlement->entitlement;
    }

    public function requireEntitlement($entitlement) {
        if(NULL === $this->_grantedEntitlement) {
            // FIXME: is this actually true? maybe the entitlement can truely be "null"?
            throw new Exception("need to verify the token first");
        }
        $grantedEntitlement = explode(" ", $this->_grantedEntitlement);
        if(!in_array($entitlement, $grantedEntitlement)) {
            throw new VerifyException("insufficient_entitlement", "no permission for this call with granted entitlement");
        }
    }

    public function requireScope($scope) {
        if(NULL === $this->_bearerToken) {
            // this is a programmer error
            throw new Exception("need to verify the token first");
        }
        $grantedScope = new Scope($this->_bearerToken->scope);
        $requiredScope = new Scope($scope);
        if(FALSE === $grantedScope->hasScope($requiredScope)) {
            throw new VerifyException("insufficient_scope", "no permission for this call with granted scope");
        }
    }

    public function getResourceOwnerId() {
        if(NULL === $this->_bearerToken) {
            // this is a programmer error
            throw new Exception("need to verify the token first");
        }
        return $this->_bearerToken->resource_owner_id;
    }

}

?>
