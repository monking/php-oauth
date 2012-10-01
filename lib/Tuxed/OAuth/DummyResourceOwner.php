<?php

namespace Tuxed\OAuth;

use \Tuxed\Config as Config;

class DummyResourceOwner implements IResourceOwner {

    private $_c;

    public function __construct(Config $c) {
        $this->_c = $c;
    }

    public function setHint($resourceOwnerIdHint = NULL) {
        // this resource owner class does not support hinting
    }

    public function getAttributes() {
        $attributes = $this->_c->getSectionValue('DummyResourceOwner', 'resourceOwnerAttribute', FALSE);

        // FIXME: entitlement from config file is not an array :(
        if(NULL === $attributes) {
            return array();
        }
        if(array_key_exists("entitlement", $attributes)) {
            $attributes['entitlement'] = array($attributes['entitlement']);
        }
        return $attributes;
        
        //return (NULL !== $attributes) ? $attributes : array();
    }

    public function getAttribute($key) {
        $attributes = $this->getAttributes();
        return array_key_exists($key, $attributes) ? $attributes[$key] : NULL;
    }

    public function getResourceOwnerId() {
        return $this->_c->getSectionValue('DummyResourceOwner', 'resourceOwnerId');
    }

    /* FIXME: DEPRECATED */
    public function getEntitlement() {
        return $this->getAttribute("entitlement");
    }
}
