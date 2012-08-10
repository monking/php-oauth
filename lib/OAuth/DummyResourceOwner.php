<?php

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
        $entitlements = array();
        foreach($this->_c->getSectionValue("DummyResourceOwner", "resourceOwnerEntitlement") as $k => $v) {
            if($v === $this->getResourceOwnerId()) {
                array_push($entitlements, $k);
            }
        }
        return implode(" ", $entitlements);
    }
}

?>
