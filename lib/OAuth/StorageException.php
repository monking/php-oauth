<?php

/**
 * When something went wrong with storing or retrieving 
 * something storage
 */
class StorageException extends Exception {

    public function getLogMessage($includeTrace = FALSE) {
        $msg = 'Message    : ' . $this->getMessage() . PHP_EOL;
        if($includeTrace) {
            $msg .= 'Trace      : ' . $this->getTraceAsString() . PHP_EOL;
        }
        return $msg;
    }

}

?>
