<?php

/**
 * Thrown when interaction with the token endpoint fails
 * https://tools.ietf.org/html/draft-ietf-oauth-v2-26#section-5.2
 */
class TokenException extends Exception {

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
            case "invalid_client":
                return 401;
            default:           
                return 400;
        }
    }

    public function getLogMessage($includeTrace = FALSE) {
        $msg = 'Message    : ' . $this->getMessage() . PHP_EOL .
               'Description: ' . $this->getDescription() . PHP_EOL;
        if($includeTrace) {
            $msg .= 'Trace      : ' . $this->getTraceAsString() . PHP_EOL;
        }
        return $msg;
    }

}

?>
