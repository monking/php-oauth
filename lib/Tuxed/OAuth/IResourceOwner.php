<?php

namespace Tuxed\OAuth;

interface IResourceOwner {
    public function setHint                    ($resourceOwnerIdHint = NULL);
    public function getAttributes              ();
    public function getResourceOwnerId         ();
    public function getEntitlement             ();
}
