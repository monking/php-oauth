<?php

namespace Tuxed\OAuth\Client;

interface IOAuthClientStorage {
    public function getAccessToken($resourceOwnerId, $scope);
    public function storeAccessToken($accessToken, $issueTime, $resourceOwnerId, $scope, $expiresIn);
    public function storeState($state, $redirectUri);
    public function getStateByState($state);
    public function getStateByCode($code);
    public function updateState($state, $redirectUri, $code);
    public function deleteAccessToken($accessToken);
    public function deleteState($state);
}
