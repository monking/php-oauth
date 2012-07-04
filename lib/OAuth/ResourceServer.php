<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "Config.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "IOAuthStorage.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "VerifyException.php";

class ResourceServer {

    private $_storage;
    private $_c;

    public function __construct(IOAuthStorage $storage, Config $c) {
        $this->_storage = $storage;
        $this->_c = $c;
    }

    // verify using database
    public function verify($authorizationHeader) {
        // b64token = 1*( ALPHA / DIGIT / "-" / "." / "_" / "~" / "+" / "/" ) *"="
        $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';
        $result = preg_match('|^Bearer (?P<value>' . $b64TokenRegExp . ')$|', $authorizationHeader, $matches);
        if($result === FALSE || $result === 0) {
            throw new VerifyException("invalid_token", "the access token is malformed");
        }
        $accessToken = $matches['value'];
        $token = $this->_storage->getAccessToken($accessToken);
        if($token === FALSE) {
            throw new VerifyException("invalid_token", "the access token is invalid");
        }
        if(time() > $token->issue_time + $token->expires_in) {
            throw new VerifyException("invalid_token", "the access token expired");
        }
        return $token;
    }

}


?>
