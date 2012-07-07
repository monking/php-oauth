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

    public function getLogMessage($includeTrace = FALSE) {
        $msg = 'Message    : ' . $this->getMessage() . PHP_EOL .
               'Description: ' . $this->getDescription() . PHP_EOL .
               'Client     : ' . $this->getClient() . PHP_EOL .
               'State      : ' . $this->getState() . PHP_EOL;
        if($includeTrace) {
            $msg .= 'Trace      : ' . PHP_EOL . $this->getTraceAsString() . PHP_EOL;
        }
        return $msg;
    }

}

?>
