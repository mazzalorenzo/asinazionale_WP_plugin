<?php

namespace Asinazionale\Core;

class ErrorHandler {
    public function __construct() {
        add_action('asinazionale_show_error', [$this, 'user_log'], accepted_args: 2);
        add_action('asinazionale_log', [$this, 'log'], accepted_args: 1);
    }

    public function init($error, $message, $user_message = null) {
        if( $user_message !== null) {
            $this->user_log($error, $user_message);
        }
        $this->console_log($error, $message);
        $this->debug_log($error, $message);
    }

    public function log($message){
        if( is_admin() ) {
            wp_print_inline_script_tag("console.log('$message')");
        }
            wp_print_inline_script_tag("console.log('$message')");

        error_log($message);
    }

    public function user_log($message = null, $title = 'Errore') {
        if (empty($message)) {
            return;
        }
        echo '
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <h4 class="alert-heading">' . esc_html($title) . '</h4>
            <p class="mb-0">' . esc_html($message) . '</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}
