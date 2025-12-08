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
        echo 
        '<div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">' . $title . '</h4>
        <p>' . $message . '</p>
           <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>';
#        include ASINAZIONALE_PLUGIN_PATH . 'templates/error-message.php';
        echo 
        '<div class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modal title</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Modal body text goes here.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>';

    }
}