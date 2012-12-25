<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OAuth;

use \RestService\Utils\Config as Config;
use \BrowserIDVerifier as BrowserIDVerifier;

class BrowserIDResourceOwner implements IResourceOwner
{
    private $_config;
    private $_verifier;
    private $_resourceOwnerIdHint;

    public function __construct(Config $c)
    {
        $this->_c = $c;

        $bPath = $this->_c->getSectionValue('BrowserIDResourceOwner', 'browserIDPath') . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'BrowserIDVerifier.php';
        if (!file_exists($bPath) || !is_file($bPath) || !is_readable($bPath)) {
            throw new BrowserIDResourceOwnerException("invalid path to php-browserid");
        }
        require_once $bPath;

        $this->_verifier = new BrowserIDVerifier($this->_c->getSectionValue('BrowserIDResourceOwner', 'verifierAddress'));
    }

    public function setHint($resourceOwnerIdHint = NULL)
    {
        $this->_resourceOwnerIdHint = $resourceOwnerIdHint;
    }

    public function getAttributes()
    {
        return json_encode(array());
    }

    public function getResourceOwnerId()
    {
        return $this->_verifier->authenticate($this->_resourceOwnerIdHint);
    }

    public function getEntitlement()
    {
        $resourceOwnerEntitlement = $this->_c->getSectionValue("BrowserIDResourceOwner", "resourceOwnerEntitlement", FALSE);
        if (!is_array($resourceOwnerEntitlement)) {
            return NULL;
        }

        $entitlements = array();
        foreach ($resourceOwnerEntitlement as $k => $v) {
            if ($v === $this->getResourceOwnerId()) {
                array_push($entitlements, $k);
            }
        }

            return empty($entitlements) ? NULL : implode(" ", $entitlements);
    }

}
