<?php

namespace Asinazionale\Core;

class Enqueue {

    public function __construct(){
        add_action('wp_enqueue_scripts', [$this, 'assets']);
    }

    function assets() {
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
            array(),
            '5.3.8'
        );
        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.8',
            true
        );
    #    wp_enqueue_style('asinazionale-style', ASINAZIONALE_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0');
        wp_enqueue_script('asinazionale-script', ASINAZIONALE_PLUGIN_URL . 'assets/js/script.js', array('jquery'), '1.0.0', true);
    }

}