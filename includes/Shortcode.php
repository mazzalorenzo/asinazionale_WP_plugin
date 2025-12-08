<?php

namespace Asinazionale;

class Shortcode {


    public function __construct() {
        add_shortcode('pulsante_asinazionale', [$this, 'pulsante_asinazionale']);
    }
    function pulsante_asinazionale() {
        if (isset($_POST['asinazionale']) && !empty($_POST['cf'])) {
            $cf = sanitize_text_field($_POST['cf']);
            (new \Asinazionale\Website\Website()) -> asinazionale($cf);
    #        $asinazionale -> asinazionale($cf);
        }
        ob_start();
        include ASINAZIONALE_PLUGIN_PATH . 'templates/shortcode.php';
        return ob_get_clean();
    }


}