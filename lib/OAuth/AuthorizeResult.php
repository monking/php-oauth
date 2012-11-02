<?php

namespace OAuth;

use \RestService\Http\Uri as Uri;

class AuthorizeResult
{
    const REDIRECT     = 100;
    const ASK_APPROVAL = 200;

    private $_action;
    private $_redirectUri;
    private $_client;
    private $_scope;

    public function __construct($action)
    {
        if (!in_array($action, array (self::REDIRECT, self::ASK_APPROVAL))) {
            throw new AuthorizeResultException("invalid action");
        }
        $this->_action = $action;
        $this->_redirectUri = NULL;
        $this->_client = NULL;
        $this->_scope = NULL;
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function setRedirectUri(Uri $u)
    {
        if (self::REDIRECT !== $this->_action) {
            throw new AuthorizeResultException("cannot set url for this action");
        }
        $this->_redirectUri = $u;
    }

    public function getRedirectUri()
    {
        if (self::REDIRECT !== $this->_action) {
            throw new AuthorizeResultException("cannot get url for this action");
        }

        return $this->_redirectUri;
    }

    public function setClient(ClientRegistration $c)
    {
        if (self::ASK_APPROVAL !== $this->_action) {
            throw new AuthorizeResultException("cannot set client for this action");
        }
        $this->_client = $c;
    }

    public function getClient()
    {
        if (self::ASK_APPROVAL !== $this->_action) {
            throw new AuthorizeResultException("cannot get client for this action");
        }

        return $this->_client;
    }

    public function setScope(Scope $s)
    {
        if (self::ASK_APPROVAL !== $this->_action) {
            throw new AuthorizeResultException("cannot set scope for this action");
        }
        $this->_scope = $s;
    }

    public function getScope()
    {
        if (self::ASK_APPROVAL !== $this->_action) {
            throw new AuthorizeResultException("cannot get scope for this action");
        }

        return $this->_scope;
    }

}
