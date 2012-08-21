<?php

namespace Tuxed\OAuth;

use \Tuxed\Config as Config;

class DummyResourceOwner implements IResourceOwner {

    private $_c;

    public function __construct(Config $c) {
        $this->_c = $c;
    }

    public function setHint($resourceOwnerIdHint = NULL) {
    }

    public function getResourceOwnerId() {
        return $this->_c->getSectionValue('DummyResourceOwner', 'resourceOwnerId');
    }

    public function getEntitlement() {
        $resourceOwnerEntitlement = $this->_c->getSectionValue("DummyResourceOwner", "resourceOwnerEntitlement", FALSE);
        if(!is_array($resourceOwnerEntitlement)) {
            return NULL;
        }

        $entitlements = array();
        foreach($resourceOwnerEntitlement as $k => $v) {
            if($v === $this->getResourceOwnerId()) {
                array_push($entitlements, $k);
            }
        }
        	return empty($entitlements) ? NULL : implode(" ", $entitlements);
    }
}
