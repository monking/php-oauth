<?php 

require_once __DIR__ . DIRECTORY_SEPARATOR . "IOAuthStorage.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "IResourceOwner.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "StorageException.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "Scope.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "TokenException.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "ClientException.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "ResourceOwnerException.php";

class AuthorizationServer {

    private $_storage;
    private $_c;

    public function __construct(IOAuthStorage $storage, Config $c) {
        $this->_storage = $storage;
        $this->_c = $c;
    }
 
    public function authorize(IResourceOwner $resourceOwner, array $get) {
        try { 
            $clientId     = self::getParameter($get, 'client_id');
            $responseType = self::getParameter($get, 'response_type');
            $redirectUri  = self::getParameter($get, 'redirect_uri');
            $scope        = new Scope(self::getParameter($get, 'scope'));
            $state        = self::getParameter($get, 'state');

            if(NULL === $clientId) {
                throw new ResourceOwnerException('client_id missing');
            }

            if(NULL === $responseType) {
                throw new ResourceOwnerException('response_type missing');
            }

            $client = $this->_storage->getClient($clientId);
            if(FALSE === $client) {
                throw new ResourceOwnerException('client not registered');
            }

            if(NULL !== $redirectUri) {
                if($client->redirect_uri !== $redirectUri) {
                    throw new ResourceOwnerException('specified redirect_uri not the same as registered redirect_uri');
                }
            }

            // we need to make sure the client can only request the grant types belonging to its profile
            $allowedClientProfiles = array ( "web_application" => array ("code"),
                                             "native_application" => array ("token", "code"),
                                             "user_agent_based_application" => array ("token"));

            if(!in_array($responseType, $allowedClientProfiles[$client->type])) {
                throw new ClientException("unsupported_response_type", "response_type not supported by client profile", $client, $state);
            }

            if(!$scope->isSubsetOf(new Scope($client->allowed_scope))) {
                throw new ClientException("invalid_scope", "not authorized to request this scope", $client, $state);
            }

            $this->_storage->updateEntitlement($resourceOwner->getResourceOwnerId(), $resourceOwner->getEntitlement());

            $approvedScope = $this->_storage->getApproval($clientId, $resourceOwner->getResourceOwnerId(), $scope->getScope());
            if(FALSE === $approvedScope || FALSE === $scope->isSubsetOf(new Scope($approvedScope->scope))) {
                return array ("action" => "ask_approval", "client" => $client);
            } else {
                if("token" === $responseType) {
                    // implicit grant
                    // FIXME: return existing access token if it exists for this exact client, resource owner and scope?
                    $accessToken = self::randomHex(16);
                    $this->_storage->storeAccessToken($accessToken, time(), $clientId, $resourceOwner->getResourceOwnerId(), $scope->getScope(), $this->_c->getValue('accessTokenExpiry'));
                    $token = array("access_token" => $accessToken, 
                                   "expires_in" => $this->_c->getValue('accessTokenExpiry'), 
                                   "token_type" => "bearer", 
                                   "scope" => $scope->getScope());
                    if(NULL !== $state) {
                        $token += array ("state" => $state);
                    }
                    return array("action" => "redirect", "url" => $client->redirect_uri . "#" . http_build_query($token));
                } else {
                    // authorization code grant
                    $authorizationCode = self::randomHex(16);
                    $this->_storage->storeAuthorizationCode($authorizationCode, $resourceOwner->getResourceOwnerId(), time(), $clientId, $redirectUri, $scope->getScope());
                    $token = array("code" => $authorizationCode);
                    if(NULL !== $state) {
                        $token += array ("state" => $state);
                    }
                    return array("action" => "redirect", "url" => $client->redirect_uri . "?" . http_build_query($token));
                }
            }
        } catch (ScopeException $e) {
            throw new ClientException("invalid_scope", "malformed scope", $client, $state);
        }
    }

    public function approve(IResourceOwner $resourceOwner, array $get, array $post) {
        try { 
            $clientId     = self::getParameter($get, 'client_id');
            $responseType = self::getParameter($get, 'response_type');
            $redirectUri  = self::getParameter($get, 'redirect_uri');
            $scope        = new Scope(self::getParameter($get, 'scope'));
            $state        = self::getParameter($get, 'state');

            $result = $this->authorize($resourceOwner, $get);
            $postScope = new Scope(implode(" ", self::getParameter($post, 'scope')));
            $approval = self::getParameter($post, 'approval');

            if($result['action'] !== "ask_approval") {
                return $result;
            }

            if("Approve" === $approval) {
                if(!$postScope->isSubsetOf($scope)) {
                    // FIXME: should this actually be an authorize exception? this is a user error!
                    throw new ClientException("invalid_scope", "approved scope is not a subset of requested scope", $client, $state);
                }

                $approvedScope = $this->_storage->getApproval($clientId, $resourceOwner->getResourceOwnerId());
                if(FALSE === $approvedScope) {
                    // no approved scope stored yet, new entry
                    $this->_storage->addApproval($clientId, $resourceOwner->getResourceOwnerId(), $postScope->getScope());
                } else if(!$postScope->isSubsetOf(new Scope($approvedScope->scope))) {
                    // not a subset, merge and store the new one
                    $mergedScopes = clone $postScope;
                    $mergedScopes->mergeWith(new Scope($approvedScope->scope));
                    $this->_storage->updateApproval($clientId, $resourceOwner->getResourceOwnerId(), $mergedScopes->getScope());
                } else {
                    // subset, approval for superset of scope already exists, do nothing
                }
                $get['scope'] = $postScope->getScope();
                return $this->authorize($resourceOwner, $get);

            } else {
                $client = $this->_storage->getClient($clientId);
                throw new ClientException("access_denied", "not authorized by resource owner", $client, $state);
            }
        } catch (ScopeException $e) {
            throw new ClientException("invalid_scope", "malformed scope", $client, $state);
        }
    }

    public function token(array $post, $user = NULL, $pass = NULL) {
        // exchange authorization code for access token
        $grantType    = self::getParameter($post, 'grant_type');
        $code         = self::getParameter($post, 'code');
        $redirectUri  = self::getParameter($post, 'redirect_uri');
        $refreshToken = self::getParameter($post, 'refresh_token');
        $token        = self::getParameter($post, 'token');

        if(NULL === $grantType) {
            throw new TokenException("invalid_request", "the grant_type parameter is missing");
        }

        switch($grantType) {
            case "urn:pingidentity.com:oauth2:grant_type:validate_bearer":
                if(NULL === $token) {
                    throw new TokenException("invalid_request", "the token parameter is missing");
                }
                $accessToken = $this->_storage->getAccessToken($token);
                if(FALSE === $accessToken) {
                    throw new TokenException("invalid_grant", "the token was not found");
                }
                $accessToken->token_type = "urn:pingidentity.com:oauth2:validated_token";
                // FIXME: update the expires_in field to show the actual amount of seconds it is still valid?
                $accessToken->resource_owner_entitlement = $this->_storage->getEntitlement($accessToken->resource_owner_id);
                return $accessToken;
            
            case "authorization_code":
                if(NULL === $code) {
                    throw new TokenException("invalid_request", "the code parameter is missing");
                }
                $result = $this->_storage->getAuthorizationCode($code, $redirectUri);
                if(FALSE === $result) {
                    throw new TokenException("invalid_grant", "the authorization code was not found");
                }
                if(time() > $result->issue_time + 600) {
                    throw new TokenException("invalid_grant", "the authorization code expired");
                }
                break;

            case "refresh_token":
                if(NULL === $refreshToken) {
                    throw new TokenException("invalid_request", "the refresh_token parameter is missing");
                }
                $result = $this->_storage->getRefreshToken($refreshToken);        
                if(FALSE === $result) {
                    throw new TokenException("invalid_grant", "the refresh_token was not found");
                }
                break;

            default:
                throw new TokenException("unsupported_grant_type", "the requested grant type is not supported");
        }

        $client = $this->_storage->getClient($result->client_id);
        if("user_agent_based_application" === $client->type) {
            throw new TokenException("unauthorized_client", "this client type is not allowed to use the token endpoint");
        }
        if("web_application" === $client->type) {
            // REQUIRE basic auth
            if(NULL === $user || empty($user) || NULL === $pass || empty($pass)) {
                throw new TokenException("invalid_client", "this client requires authentication");
            }
        
            if($user !== $client->id || $pass !== $client->secret) {
                throw new TokenException("invalid_client", "client authentication failed");
            }
        }
        if("native_application" === $client->type) {
            // MAY use basic auth, so only check when Authorization header is provided
            if(NULL !== $user && !empty($user) && NULL !== $pass && !empty($pass)) {
                if($user !== $client->id || $pass !== $client->secret) {
                    throw new TokenException("invalid_client", "client authentication failed");
                }
            }
        }

        if($client->id !== $result->client_id) {
            throw new TokenException("invalid_grant", "grant was not issued to this client");
        }

        // create a new access token
        // FIXME: return existing access token if it exists for this exact client, resource owner and scope?
        $accessToken = self::randomHex(16);
        $this->_storage->storeAccessToken($accessToken, time(), $result->client_id, $result->resource_owner_id, $result->scope, $this->_c->getValue('accessTokenExpiry'));
        $token = $this->_storage->getAccessToken($accessToken);

        if("authorization_code" === $grantType) {
            // we need to be able to delete, otherwise someone else was first!
            if(FALSE === $this->_storage->deleteAuthorizationCode($code, $redirectUri)) {
                throw new TokenException("invalid_grant", "this grant was already used");
            }
            // create a refresh token as well
            // FIXME: return existing refresh token if it exists for this exact client, resource owner and scope!
            $token->refresh_token = self::randomHex(16);
            $this->_storage->storeRefreshToken($token->refresh_token, $token->client_id, $token->resource_owner_id, $token->scope);
        } else {
            // refresh_token
            // just return the generated access_token
        }

        $token->expires_in = $token->issue_time + $token->expires_in - time();
        $token->token_type = 'bearer';
        // filter unwanted response parameters
        $responseParameters = array("access_token", "token_type", "expires_in", "refresh_token", "scope");
        foreach($token as $k => $v) {
            if(!in_array($k, $responseParameters)) {
                unset($token->$k);
            }
        }
        return $token;
    }

    private static function getParameter(array $parameters, $key) {
        return (array_key_exists($key, $parameters) && !empty($parameters[$key])) ? $parameters[$key] : NULL;
    }

    public static function randomHex($len = 16) {
        $randomString = bin2hex(openssl_random_pseudo_bytes($len, $strong));
        // @codeCoverageIgnoreStart
        if (FALSE === $strong) {
            throw new Exception("unable to securely generate random string");
        }
        // @codeCoverageIgnoreEnd
        return $randomString;
    }

}

?>
