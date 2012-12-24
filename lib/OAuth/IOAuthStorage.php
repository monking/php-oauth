<?php

namespace OAuth;

interface IOAuthStorage
{
    public function storeAccessToken         ($accessToken, $issueTime, $clientId, $resourceOwnerId, $scope, $expiry);
    public function getAccessToken           ($accessToken);
    public function storeAuthorizationCode   ($authorizationCode, $resourceOwnerId, $issueTime, $clientId, $redirectUri, $scope);
    public function getAuthorizationCode     ($clientId, $authorizationCode, $redirectUri);
    public function deleteAuthorizationCode  ($clientId, $authorizationCode, $redirectUri);
    public function deleteExpiredAccessTokens();
    public function deleteExpiredAuthorizationCodes();

    public function getApprovalByRefreshToken ($clientId, $refreshToken);
    public function getApprovalByResourceOwnerId ($clientId, $resourceOwnerId);

    public function getClients               ();
    public function getClient                ($clientId);

    public function addClient                ($data);
    public function updateClient             ($clientId, $data);
    public function deleteClient             ($clientId);

    public function getApprovals             ($resourceOwnerId);
    public function addApproval              ($clientId, $resourceOwnerId, $scope, $refreshToken);

    // FIXME: should we also update the refresh_token on token update?
    public function updateApproval           ($clientId, $resourceOwnerId, $scope);
    public function deleteApproval           ($clientId, $resourceOwnerId);

    public function updateResourceOwner      ($resourceOwnerId, $resourceOwnerAttributes);
    public function getResourceOwner         ($resourceOwnerId);
}
