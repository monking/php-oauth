<?php

/**
 * Thrown when the verification of the access token fails
 */
class VerifyException extends Exception {

    private $_description;

    public function __construct($message, $description, $code = 0, Exception $previous = null) {
        $this->_description = $description;
        parent::__construct($message, $code, $previous);
    }

    public function getDescription() {
        return $this->_description;
    }

    public function getResponseCode() {
        switch($this->message) {
            case "invalid_request":
                return 400;
            case "invalid_token":
                return 401;
            case "insufficient_scope":
                return 403;
            default:
                return 400;
        }
    }

}

?>
