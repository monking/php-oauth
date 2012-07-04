<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "VerifyException.php";

class RemoteResourceServer {

    private $_tokenEndpoint;

    public function __construct($tokenEndpoint) {
        $this->_tokenEndpoint = $tokenEndpoint;
    }

    public function verify($authorizationHeader) {
        // b64token = 1*( ALPHA / DIGIT / "-" / "." / "_" / "~" / "+" / "/" ) *"="
        $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';
        $result = preg_match('|^Bearer (?P<value>' . $b64TokenRegExp . ')$|', $authorizationHeader, $matches);
        if($result === FALSE || $result === 0) {
            throw new VerifyException("invalid_token", "the access token is malformed");
        }
        $accessToken = $matches['value'];

        // get the token from the server
        $ch = curl_init();
        $post = array("token" => $accessToken, "grant_type" => "urn:pingidentity.com:oauth2:grant_type:validate_bearer");
        curl_setopt($ch, CURLOPT_URL, $this->_tokenEndpoint);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_FAILONERROR, FALSE);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Authorization: Basic ABCDEF"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

        // grab URL and pass it to the browser
        $data = curl_exec($ch);
        if(FALSE === $data) {
            error_log(curl_error($c));
            throw new VerifyException("invalid_token", "unable to verify the access token");
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(200 !== $code) {
            error_log("error: " . $data);
            throw new VerifyException("invalid_token", "the access token is invalid");
        }
        curl_close($ch);
        $d = json_decode($data, TRUE);
        return $d;
    }

}

?>
