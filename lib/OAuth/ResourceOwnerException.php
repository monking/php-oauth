<?php

/**
 * Thrown when the resource owner needs to be  informed of an error
 */
class ResourceOwnerException extends Exception {

    public function getLogMessage($includeTrace = FALSE) {
        $msg = 'Message    : ' . $this->getMessage() . PHP_EOL;
        if($includeTrace) {
            $msg .= 'Trace      : ' . PHP_EOL . $this->getTraceAsString() . PHP_EOL;
        }
        return $msg;
    }

}

?>
