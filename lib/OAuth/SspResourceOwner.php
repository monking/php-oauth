<?php

namespace OAuth;

use \RestService\Utils\Config as Config;
use \SimpleSAML_Auth_Simple as SimpleSAML_Auth_Simple;

class SspResourceOwner implements IResourceOwner
{
    private $_c;
    private $_ssp;

    public function __construct(Config $c)
    {
        $this->_c = $c;
        $sspPath = $this->_c->getSectionValue('SspResourceOwner', 'sspPath') . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '_autoload.php';
        if (!file_exists($sspPath) || !is_file($sspPath) || !is_readable($sspPath)) {
            throw new SspResourceOwnerException("invalid path to simpleSAMLphp");
        }
        require_once $sspPath;

        $this->_ssp = new SimpleSAML_Auth_Simple($this->_c->getSectionValue('SspResourceOwner', 'authSource'));
    }

    public function setHint($resourceOwnerIdHint = NULL)
    {
        // this resource owner class does not support hinting
    }

    private function _authenticateUser()
    {
        $this->_ssp->requireAuth(array("saml:NameIDPolicy" => "urn:oasis:names:tc:SAML:2.0:nameid-format:persistent"));
    }

    public function getAttributes()
    {
        $this->_authenticateUser();

        return $this->_ssp->getAttributes();
    }

    public function getAttribute($key)
    {
        $attributes = $this->getAttributes();

        return array_key_exists($key, $attributes) ? $attributes[$key] : NULL;
    }

    public function getResourceOwnerId()
    {
        $this->_authenticateUser();
        $nameId = $this->_ssp->getAuthData("saml:sp:NameID");
        if ("urn:oasis:names:tc:SAML:2.0:nameid-format:persistent" !== $nameId['Format']) {
            throw new SspResourceOwnerException("NameID format not equal to 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'");
        }

        return $nameId['Value'];
    }

    /* FIXME: DEPRECATED */
    public function getEntitlement()
    {
        return $this->getAttribute("eduPersonEntitlement");
    }

}
