<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "IOAuthStorage.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "StorageException.php";
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "Config.php";

/**
 * Class to implement storage for the OAuth Authorization Server using PDO.
 *
 * FIXME: look into throwing exceptions on error instead of returning FALSE?
 * FIXME: switch to ASSOC instead of OBJ return types
 */
class PdoOAuthStorage implements IOAuthStorage {

    private $_c;
    private $_pdo;

    public function __construct(Config $c) {
        $this->_c = $c;

        $driverOptions = array();
        if(TRUE === $this->_c->getSectionValue('PdoOAuthStorage', 'persistentConnection')) {
            $driverOptions = array(PDO::ATTR_PERSISTENT => TRUE);
        }

        $this->_pdo = new PDO($this->_c->getSectionValue('PdoOAuthStorage', 'dsn'), $this->_c->getSectionValue('PdoOAuthStorage', 'username', FALSE), $this->_c->getSectionValue('PdoOAuthStorage', 'password', FALSE), $driverOptions);

        	$this->_pdo->exec("PRAGMA foreign_keys = ON");
    }

    public function getClients() {
        $stmt = $this->_pdo->prepare("SELECT * FROM Client");
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to retrieve clients");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function getClient($clientId) {
        $stmt = $this->_pdo->prepare("SELECT * FROM Client WHERE id = :client_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to retrieve client");
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function updateClient($clientId, $data) {
        $stmt = $this->_pdo->prepare("UPDATE Client SET name = :name, description = :description, secret = :secret, redirect_uri = :redirect_uri, type = :type, icon = :icon, allowed_scope = :allowed_scope WHERE id = :client_id");
        $stmt->bindValue(":name", $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(":description", $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(":secret", $data['secret'], PDO::PARAM_STR);
        $stmt->bindValue(":redirect_uri", $data['redirect_uri'], PDO::PARAM_STR);
        $stmt->bindValue(":type", $data['type'], PDO::PARAM_STR);
        $stmt->bindValue(":icon", $data['icon'], PDO::PARAM_STR);
        $stmt->bindValue(":allowed_scope", $data['allowed_scope'], PDO::PARAM_STR);
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to update client");
        }
        return 1 === $stmt->rowCount();
    }

    public function addClient($data) {
        $stmt = $this->_pdo->prepare("INSERT INTO Client (id, name, description, secret, redirect_uri, type, icon, allowed_scope) VALUES(:client_id, :name, :description, :secret, :redirect_uri, :type, :icon, :allowed_scope)");
        $stmt->bindValue(":client_id", $data['id'], PDO::PARAM_STR);
        $stmt->bindValue(":name", $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(":description", $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(":secret", $data['secret'], PDO::PARAM_STR | PDO::PARAM_NULL);
        $stmt->bindValue(":redirect_uri", $data['redirect_uri'], PDO::PARAM_STR);
        $stmt->bindValue(":type", $data['type'], PDO::PARAM_STR);
        $stmt->bindValue(":icon", $data['icon'], PDO::PARAM_STR);
        $stmt->bindValue(":allowed_scope", $data['allowed_scope'], PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to add client");
        }
        return 1 === $stmt->rowCount();
    }

    public function deleteClient($clientId) {
        // delete approvals
        $stmt = $this->_pdo->prepare("DELETE FROM Approval WHERE client_id = :client_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete approvals");
        }
        // delete access tokens
        $stmt = $this->_pdo->prepare("DELETE FROM AccessToken WHERE client_id = :client_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete access tokens");
        }
        // delete authorization codes
        $stmt = $this->_pdo->prepare("DELETE FROM AuthorizationCode WHERE client_id = :client_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete authorization codes");
        }
        // delete refresh tokens
        $stmt = $this->_pdo->prepare("DELETE FROM RefreshToken WHERE client_id = :client_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete refresh tokens");
        }
        // delete the client
        $stmt = $this->_pdo->prepare("DELETE FROM Client WHERE id = :client_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete client");
        }
        return 1 === $stmt->rowCount();
    }

    public function addApproval($clientId, $resourceOwnerId, $scope) {
        $stmt = $this->_pdo->prepare("INSERT INTO Approval (client_id, resource_owner_id, scope) VALUES(:client_id, :resource_owner_id, :scope)");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to store approved scope");
        }
        return 1 === $stmt->rowCount();
    }

    public function updateApproval($clientId, $resourceOwnerId, $scope) {
        $stmt = $this->_pdo->prepare("UPDATE Approval SET scope = :scope WHERE client_id = :client_id AND resource_owner_id = :resource_owner_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to update approved scope");
        }
        return 1 === $stmt->rowCount();
    }

    public function getApproval($clientId, $resourceOwnerId) {
        $stmt = $this->_pdo->prepare("SELECT * FROM Approval WHERE client_id = :client_id AND resource_owner_id = :resource_owner_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get approved scope");
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function storeAccessToken($accessToken, $issueTime, $clientId, $resourceOwnerId, $scope, $expiry) {
        $stmt = $this->_pdo->prepare("INSERT INTO AccessToken (client_id, resource_owner_id, issue_time, expires_in, scope, access_token) VALUES(:client_id, :resource_owner_id, :issue_time, :expires_in, :scope, :access_token)");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", time(), PDO::PARAM_INT);
        $stmt->bindValue(":expires_in", $expiry, PDO::PARAM_INT);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to store access token");
        }
        return 1 === $stmt->rowCount();
    }

    public function storeAuthorizationCode($authorizationCode, $resourceOwnerId, $issueTime, $clientId, $redirectUri, $scope) {
        $stmt = $this->_pdo->prepare("INSERT INTO AuthorizationCode (client_id, resource_owner_id, authorization_code, redirect_uri, issue_time, scope) VALUES(:client_id, :resource_owner_id, :authorization_code, :redirect_uri, :issue_time, :scope)");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":authorization_code", $authorizationCode, PDO::PARAM_STR);
        $stmt->bindValue(":redirect_uri", $redirectUri, PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", time(), PDO::PARAM_INT);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to store authorization code");
        }
        return 1 === $stmt->rowCount();
    }

    public function getAuthorizationCode($authorizationCode, $redirectUri) {
$stmt = $this->_pdo->prepare("SELECT * FROM AuthorizationCode WHERE authorization_code IS :authorization_code AND redirect_uri IS :redirect_uri");
        $stmt->bindValue(":authorization_code", $authorizationCode, PDO::PARAM_STR);
        $stmt->bindValue(":redirect_uri", $redirectUri, PDO::PARAM_STR | PDO::PARAM_NULL);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get authorization code");
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function deleteAuthorizationCode($authorizationCode, $redirectUri) {
        $stmt = $this->_pdo->prepare("DELETE FROM AuthorizationCode WHERE authorization_code IS :authorization_code AND redirect_uri IS :redirect_uri");
        $stmt->bindValue(":authorization_code", $authorizationCode, PDO::PARAM_STR);
        $stmt->bindValue(":redirect_uri", $redirectUri, PDO::PARAM_STR | PDO::PARAM_NULL);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to delete authorization code");
        }
        return 1 === $stmt->rowCount();
    }

    public function getAccessToken($accessToken) {
        $stmt = $this->_pdo->prepare("SELECT * FROM AccessToken WHERE access_token = :access_token");
        $stmt->bindValue(":access_token", $accessToken, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get access token");
        }
        return $stmt->fetch(PDO::FETCH_OBJ);        
    }

    public function getApprovals($resourceOwnerId) {
        $stmt = $this->_pdo->prepare("SELECT c.id, a.scope, c.name, c.description, c.icon, c.redirect_uri FROM Approval a, Client c WHERE resource_owner_id = :resource_owner_id AND a.client_id = c.id");
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get approvals");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function deleteApproval($clientId, $resourceOwnerId) {
        // remove refresh token
        $stmt = $this->_pdo->prepare("DELETE FROM RefreshToken WHERE client_id = :client_id AND resource_owner_id = :resource_owner_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        if (FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete approval");
        }
        // remove approval
        $stmt = $this->_pdo->prepare("DELETE FROM Approval WHERE client_id = :client_id AND resource_owner_id = :resource_owner_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        if (FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete approval");
        } 
        return 1 === $stmt->rowCount();
    }

    public function getRefreshToken($refreshToken) {
        $stmt = $this->_pdo->prepare("SELECT * FROM RefreshToken WHERE refresh_token = :refresh_token");
        $stmt->bindValue(":refresh_token", $refreshToken, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get refresh token");
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function storeRefreshToken($refreshToken, $clientId, $resourceOwnerId, $scope) {
        $stmt = $this->_pdo->prepare("INSERT INTO RefreshToken (client_id, resource_owner_id, scope, refresh_token) VALUES(:client_id, :resource_owner_id, :scope, :refresh_token)");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->bindValue(":refresh_token", $refreshToken, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to store refresh token");
        }
       return 1 === $stmt->rowCount();
    }

    public function updateEntitlement($resourceOwnerId, $entitlement) {
        $result = $this->getEntitlement($resourceOwnerId);
        if(FALSE === $result) {
            $stmt = $this->_pdo->prepare("INSERT INTO ResourceOwner (id, entitlement) VALUES(:resource_owner_id, :entitlement)");
            $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
            $stmt->bindValue(":entitlement", $entitlement, PDO::PARAM_STR);
            if(FALSE === $stmt->execute()) {
                throw new StorageException("unable to store entitlement");
            }
           return 1 === $stmt->rowCount();
        } else {
            if($result !== $entitlement) {
                $stmt = $this->_pdo->prepare("UPDATE ResourceOwner SET entitlement = :entitlement WHERE id = :resource_owner_id");
                $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
                $stmt->bindValue(":entitlement", $entitlement, PDO::PARAM_STR);
                if(FALSE === $stmt->execute()) {
                    throw new StorageException("unable to update entitlement");
                }
                return 1 === $stmt->rowCount();
            } else {
                return TRUE;
            }
        }
    }

    public function getEntitlement($resourceOwnerId) {
        $stmt = $this->_pdo->prepare("SELECT * FROM ResourceOwner WHERE id = :resource_owner_id");
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get entitlement");
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }


    public function initDatabase() {
        $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS `ResourceOwner` (
            `id` varchar(64) NOT NULL,
            `entitlement` text DEFAULT NULL,
            PRIMARY KEY (`id`))
        ");

        $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS `Client` (
            `id` varchar(64) NOT NULL,
            `name` text NOT NULL,
            `description` text NOT NULL,
            `secret` text DEFAULT NULL,
            `redirect_uri` text NOT NULL,
            `type` text NOT NULL,
            `icon` text DEFAULT NULL,
            `allowed_scope` text DEFAULT NULL,
            PRIMARY KEY (`id`))
        ");

        $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS `AccessToken` (
            `access_token` varchar(64) NOT NULL,
            `client_id` varchar(64) NOT NULL,
            `resource_owner_id` varchar(64) NOT NULL,
            `issue_time` int(11) DEFAULT NULL,
            `expires_in` int(11) DEFAULT NULL,
            `scope` text NOT NULL,
            PRIMARY KEY (`access_token`),
            FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`))
        ");

        $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS `RefreshToken` (
            `refresh_token` varchar(64) NOT NULL,
            `client_id` varchar(64) NOT NULL,
            `resource_owner_id` varchar(64) NOT NULL,
            `scope` text NOT NULL,
            PRIMARY KEY (`refresh_token`),
            FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`))
        ");

        $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS `Approval` (
            `client_id` varchar(64) NOT NULL,
            `resource_owner_id` varchar(64) NOT NULL,
            `scope` text NOT NULL,
            FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`),
            UNIQUE(`client_id`, `resource_owner_id`))
        ");

        $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS `AuthorizationCode` (
            `authorization_code` varchar(64) NOT NULL,
            `client_id` varchar(64) NOT NULL,
            `resource_owner_id` varchar(64) NOT NULL,
            `redirect_uri` text DEFAULT NULL,
            `issue_time` int(11) NOT NULL,
            `scope` text NOT NULL,
            PRIMARY KEY (`authorization_code`),
            FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`))
        ");
    }

}

?>
