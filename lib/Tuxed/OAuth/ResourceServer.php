<?php

namespace Tuxed\OAuth;

class ResourceServer {

    private $_storage;
    private $_entitlementEnforcement;
    private $_grantedEntitlement;
    private $_grantedScope;
    private $_resourceOwnerId;

    public function __construct(IOAuthStorage $s) {
        $this->_storage = $s;
        $this->_entitlementEnforcement = TRUE;
        $this->_resourceOwnerId = NULL;
        $this->_grantedScope = NULL;
        $this->_grantedEntitlement = NULL;
    }

    public function verifyAuthorizationHeader($authorizationHeader) {
        // b64token = 1*( ALPHA / DIGIT / "-" / "." / "_" / "~" / "+" / "/" ) *"="
        $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';
        $result = preg_match('|^Bearer (?P<value>' . $b64TokenRegExp . ')$|', $authorizationHeader, $matches);
        if($result === FALSE || $result === 0) {
            throw new ResourceServerException("invalid_token", "the access token is malformed");
        }
        $accessToken = $matches['value'];
        $token = $this->_storage->getAccessToken($accessToken);
        if(FALSE === $token) {
            throw new ResourceServerException("invalid_token", "the access token is invalid");
        }
        if(time() > $token->issue_time + $token->expires_in) {
            throw new ResourceServerException("invalid_token", "the access token expired");
        }
        $this->_resourceOwnerId = $token->resource_owner_id;
        $this->_grantedScope = $token->scope;
        $entitlement = $this->_storage->getEntitlement($token->resource_owner_id);
        $this->_grantedEntitlement = $entitlement->entitlement;
    }

    public function setEntitlementEnforcement($enforce = TRUE) {
        $this->_entitlementEnforcement = $enforce;
    }

    public function getResourceOwnerId() {
        // FIXME: should we die when the resourceOwnerId is NULL?
        return $this->_resourceOwnerId;
    }

    public function hasScope($scope) {
        $grantedScope = new Scope($this->_grantedScope);
        $requiredScope = new Scope($scope);
        return $grantedScope->hasScope($requiredScope);
    }

    public function requireScope($scope) {
        if(FALSE === $this->hasScope($scope)) {
            throw new ResourceServerException("insufficient_scope", "no permission for this call with granted scope");
        }
    }

    public function hasEntitlement($entitlement) {
        if(NULL === $this->_grantedEntitlement) {
            return FALSE;
        }
        $grantedEntitlement = explode(" ", $this->_grantedEntitlement);
        if(in_array($entitlement, $grantedEntitlement)) {
            return TRUE;
        }
        return FALSE;
    }

    public function requireEntitlement($entitlement) {
        if($this->_entitlementEnforcement) {
            if(FALSE === $this->hasEntitlement($entitlement)) {
                throw new ResourceServerException("insufficient_entitlement", "no permission for this call with granted entitlement");
            }
        }
    }

}
