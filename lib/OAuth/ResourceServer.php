<?php

namespace OAuth;

class ResourceServer
{
    private $_storage;
    private $_entitlementEnforcement;
    private $_resourceOwnerId;
    private $_grantedScope;
    private $_resourceOwnerAttributes;

    public function __construct(IOAuthStorage $s)
    {
        $this->_storage = $s;
        $this->_entitlementEnforcement = TRUE;
        $this->_resourceOwnerId = NULL;
        $this->_grantedScope = NULL;
        $this->_resourceOwnerAttributes = NULL;
    }

    public function verifyAuthorizationHeader($authorizationHeader)
    {
        if (NULL === $authorizationHeader) {
            throw new ResourceServerException("no_token", "no authorization header in the request");
        }
        // b64token = 1*( ALPHA / DIGIT / "-" / "." / "_" / "~" / "+" / "/" ) *"="
        $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';
        $result = preg_match('|^Bearer (?P<value>' . $b64TokenRegExp . ')$|', $authorizationHeader, $matches);
        if ($result === FALSE || $result === 0) {
            throw new ResourceServerException("invalid_token", "the access token is malformed");
        }
        $accessToken = $matches['value'];
        $token = $this->_storage->getAccessToken($accessToken);
        if (FALSE === $token) {
            throw new ResourceServerException("invalid_token", "the access token is invalid");
        }
        if (time() > $token->issue_time + $token->expires_in) {
            throw new ResourceServerException("invalid_token", "the access token expired");
        }
        $this->_resourceOwnerId = $token->resource_owner_id;
        $this->_grantedScope = $token->scope;
        $resourceOwner = $this->_storage->getResourceOwner($token->resource_owner_id);
        $this->_resourceOwnerAttributes = json_decode($resourceOwner->attributes, TRUE);
    }

    public function setEntitlementEnforcement($enforce = TRUE)
    {
        $this->_entitlementEnforcement = $enforce;
    }

    public function getResourceOwnerId()
    {
        // FIXME: should we die when the resourceOwnerId is NULL?
        return $this->_resourceOwnerId;
    }

    public function getEntitlement()
    {
        if (!array_key_exists("entitlement", $this->_resourceOwnerAttributes)) {
            return array();
        }

        return $this->_resourceOwnerAttributes['entitlement'];
    }

    public function hasScope($scope)
    {
        $grantedScope = new Scope($this->_grantedScope);
        $requiredScope = new Scope($scope);

        return $grantedScope->hasScope($requiredScope);
    }

    public function requireScope($scope)
    {
        if (FALSE === $this->hasScope($scope)) {
            throw new ResourceServerException("insufficient_scope", "no permission for this call with granted scope");
        }
    }

    public function hasEntitlement($entitlement)
    {
        if (!array_key_exists("entitlement", $this->_resourceOwnerAttributes)) {
            return FALSE;
        }

        return in_array($entitlement, $this->_resourceOwnerAttributes['entitlement']);
    }

    public function requireEntitlement($entitlement)
    {
        if ($this->_entitlementEnforcement) {
            if (FALSE === $this->hasEntitlement($entitlement)) {
                throw new ResourceServerException("insufficient_entitlement", "no permission for this call with granted entitlement");
            }
        }
    }

    public function getAttributes()
    {
        return $this->_resourceOwnerAttributes;
    }

    public function getAttribute($key)
    {
        $attributes = $this->getAttributes();

        return array_key_exists($key, $attributes) ? $attributes[$key] : NULL;
    }

}
