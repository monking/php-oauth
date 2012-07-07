<?php

class Logger {

    private $_logFile;

    public function __construct($logFile) {
        $this->_logFile = $logFile;
    }

    public function logMessage($level, $message) {
        switch($level) {
            case 10:
                $logLevel = "[INFO]   ";
                break;
            case 20:
                $logLevel = "[WARNING]";
                break;
            case 50:
                $logLevel = "[FATAL]  ";
                break;
        }
        $logMessage = date("c") . " " . $logLevel . " " . $message . PHP_EOL;
        if(FALSE === file_put_contents($this->_logFile, $logMessage, FILE_APPEND | LOCK_EX)) {
            throw new Exception("unable to write to log file");
        }
    }

    public function logInfo($message) {
        $this->logMessage(10, $message);
    }

    public function logWarn($message) {
        $this->logMessage(20, $message);
    }

    public function logFatal($message) {
        $this->logMessage(50, $message);
    }

}

?>
