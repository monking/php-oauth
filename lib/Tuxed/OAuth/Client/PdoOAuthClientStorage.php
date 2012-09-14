<?php

namespace Tuxed\OAuth\Client;

use \Tuxed\Config as Config;
use \PDO as PDO;

class PdoOAuthClientStorage implements IOAuthClientStorage {

    private $_c;
    private $_pdo;

    public function __construct(Config $c) {
        $this->_c = $c;

        $driverOptions = array();
        if($this->_c->getSectionValue('PdoOAuthClientStorage', 'persistentConnection')) {
            $driverOptions = array(PDO::ATTR_PERSISTENT => TRUE);
        }

        $this->_pdo = new PDO($this->_c->getSectionValue('PdoOAuthClientStorage', 'dsn'), $this->_c->getSectionValue('PdoOAuthClientStorage', 'username', FALSE), $this->_c->getSectionValue('PdoOAuthClientStorage', 'password', FALSE), $driverOptions);

        	$this->_pdo->exec("PRAGMA foreign_keys = ON");
    }

    public function getAccessToken($resourceOwnerId, $scope) {
        $stmt = $this->_pdo->prepare("SELECT * FROM AccessToken WHERE resource_owner_id = :resource_owner_id AND scope = :scope");
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get access token");
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);        
    }

    public function storeAccessToken($accessToken, $issueTime, $resourceOwnerId, $scope, $expiresIn) {
        $stmt = $this->_pdo->prepare("INSERT INTO AccessToken (access_token, resource_owner_id, issue_time, expires_in, scope) VALUES(:access_token, :resource_owner_id, :issue_time, :expires_in, :scope)");
        $stmt->bindValue(":resource_owner_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", time(), PDO::PARAM_INT);
        $stmt->bindValue(":expires_in", $expiresIn, PDO::PARAM_INT);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to store access token");
        }
        return 1 === $stmt->rowCount();
    }

    public function storeState($state, $redirectUri) {
        $stmt = $this->_pdo->prepare("INSERT INTO State (state, redirect_uri) VALUES(:state, :redirect_uri)");
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $stmt->bindValue(":redirect_uri", $redirectUri, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to store state");
        }
        return 1 === $stmt->rowCount();
    }

    public function getStateByState($state) {
        $stmt = $this->_pdo->prepare("SELECT * FROM State WHERE state = :state");
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get state");
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);        
    }


    public function getStateByCode($code) {
        $stmt = $this->_pdo->prepare("SELECT * FROM State WHERE code = :code");
        $stmt->bindValue(":code", $code, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new StorageException("unable to get state");
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);        
    }

    public function updateState($state, $redirectUri, $code) {
        $stmt = $this->_pdo->prepare("UPDATE State SET code = :code WHERE state = :state AND redirect_uri = :redirect_uri");
        $stmt->bindValue(":code", $code, PDO::PARAM_STR);
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $stmt->bindValue(":redirect_uri", $redirectUri, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to update state");
        }
        return 1 === $stmt->rowCount();
    }

    public function deleteAccessToken($accessToken) {
        $stmt = $this->_pdo->prepare("DELETE FROM AccessToken WHERE access_token = :access_token");
        $stmt->bindValue(":access_token", $accessToken, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete access token");
        }
        return 1 === $stmt->rowCount();
    }

    public function deleteState($state) {
        $stmt = $this->_pdo->prepare("DELETE FROM State WHERE state = :state");
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        if(FALSE === $stmt->execute()) {
            throw new StorageException("unable to delete state");
        }
        return 1 === $stmt->rowCount();
    }

    public function initDatabase() {
        $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS `AccessToken` (
            `access_token` varchar(64) NOT NULL,
            `resource_owner_id` varchar(64) NOT NULL,
            `issue_time` int(11) DEFAULT NULL,
            `expires_in` int(11) DEFAULT NULL,
            `scope` text NOT NULL,
            `refresh_token` text DEFAULT NULL,
            PRIMARY KEY (`access_token`))
        ");

        $this->_pdo->exec("
            CREATE TABLE IF NOT EXISTS `State` (
            `state` VARCHAR(64) NOT NULL,
            `redirect_uri` TEXT NOT NULL,
            `code` TEXT DEFAULT NULL,
            PRIMARY KEY (`state`))
        ");

    }

}
