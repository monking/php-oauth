<?php

/**
 * Thrown when the client needs to be informed of an error
 */
class ClientException extends Exception {

    private $_description;
    private $_client;
    private $_state;

    public function __construct($message, $description, $client, $state, $code = 0, Exception $previous = null) {
        $this->_description = $description;
        $this->_client = $client;
        $this->_state = $state;
        parent::__construct($message, $code, $previous);
    }

    public function getDescription() {
        return $this->_description;
    }

    public function getClient() {
        return $this->_client;
    }

    public function getState() {
        return $this->_state;
    }

}

?>
