<?php

namespace Asinazionale\core

class Messenger {
    public static function show($title, $user_message, $log_message, $type = null) {
        if( $type === "error" ) {
            $this->user_log($user_message);
        } else
        
        if ($type === "log") {
            $this->console_log($error, $message);
        }

        if ($type === "warning") {

        }
        $this->debug_log($error, $message);
    }

    public function console_log($error, $message){
                echo "<script>console.log('$error : $message')</script>";
    }

    public function debug_log($error, $message) {
        error_log(sprintf('%s: %s.', $error, $message));
    }

    public function user_log($message) {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { if(window.asinazionaleShowError) window.asinazionaleShowError('$message'); });</script>";
                    echo "$message";

    }

}