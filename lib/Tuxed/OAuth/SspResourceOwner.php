<?php

namespace Tuxed\OAuth;

use \Tuxed\Config as Config;
use \SimpleSAML_Auth_Simple as SimpleSAML_Auth_Simple;

class SspResourceOwner implements IResourceOwner {

    private $_c;
    private $_ssp;

    public function __construct(Config $c) {
        $this->_c = $c;
        $sspPath = $this->_c->getSectionValue('SspResourceOwner', 'sspPath') . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '_autoload.php';
        if(!file_exists($sspPath) || !is_file($sspPath) || !is_readable($sspPath)) {
            throw new SspResourceOwnerException("invalid path to simpleSAMLphp");
        }
        require_once $sspPath;

        $this->_ssp = new SimpleSAML_Auth_Simple($this->_c->getSectionValue('SspResourceOwner', 'authSource'));
    }

    public function setHint($resourceOwnerIdHint = NULL) {
    }

    private function _authenticateUser() {
        $this->_ssp->requireAuth(array("saml:NameIDPolicy" => "urn:oasis:names:tc:SAML:2.0:nameid-format:persistent"));
    }

    public function getAttributes() {
        $this->_authenticateUser();
        return json_encode($this->_ssp->getAttributes());
    }

    public function getResourceOwnerId() {
        $this->_authenticateUser();
        if($this->_c->getSectionValue('SspResourceOwner', 'useNameID')) {
            $nameId = $this->_ssp->getAuthData("saml:sp:NameID");
            if("urn:oasis:names:tc:SAML:2.0:nameid-format:persistent" !== $nameId['Format']) {
                throw new SspResourceOwnerException("NameID format not equal to 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'");
            }
            return $nameId['Value'];
        } else {
            $attributes = $this->_ssp->getAttributes();
            if(!array_key_exists($this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerIdAttributeName'), $attributes)) {
                throw new SspResourceOwnerException("resourceOwnerIdAttributeName is not available in SAML attributes");
            }
            return $attributes[$this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerIdAttributeName')][0];
        }
    }

    public function getEntitlement() {
        $this->_authenticateUser();
        $attributes = $this->_ssp->getAttributes();

        $entitlementAttributeName = $this->_c->getSectionValue('SspResourceOwner', 'entitlementAttributeName', FALSE);
        $entitlementValueMapping = $this->_c->getSectionValue("SspResourceOwner", "entitlementValueMapping", FALSE);
        if(NULL === $entitlementAttributeName || !is_array($entitlementValueMapping)) {
            return NULL;
        }

        if(!array_key_exists($entitlementAttributeName, $attributes)) {
            return NULL;
        }

        $entitlements = array();
        foreach($entitlementValueMapping as $k => $v) {
            if(in_array($v, $attributes[$entitlementAttributeName])) {
                array_push($entitlements, $k);
            }
        }
        return empty($entitlements) ? NULL : implode(" ", $entitlements);
    }

}
