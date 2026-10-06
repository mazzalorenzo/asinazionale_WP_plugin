<?php

namespace Asinazionale;

class Shortcode {

    public function __construct() {
        add_shortcode('pulsante_asinazionale', [$this, 'pulsante_asinazionale']);
        add_action('template_redirect', [$this, 'handle_download_request'], 1);
    }

    public function handle_download_request() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['asinazionale']) || empty($_POST['cf'])) {
            return;
        }

        $cf = strtoupper(preg_replace('/[^A-Z0-9]/i', '', wp_unslash($_POST['cf'])));
        if (empty($cf) || strlen($cf) < 11 || strlen($cf) > 16) {
            $referer = wp_get_referer() ?: home_url('/');
            wp_safe_redirect(add_query_arg('asi_error', rawurlencode('Codice fiscale non valido.'), $referer));
            exit;
        }

        $website = new \Asinazionale\Website\Website();
        $result = $website->asinazionale($cf);

        if (is_wp_error($result)) {
            $referer = wp_get_referer() ?: home_url('/');
            wp_safe_redirect(add_query_arg('asi_error', rawurlencode($result->get_error_message()), $referer));
            exit;
        }

        if (is_string($result) && strncmp($result, '%PDF-', 5) === 0) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            nocache_headers();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="tessera-' . $cf . '.pdf"');
            header('Content-Length: ' . strlen($result));
            echo $result;
            exit;
        } else {
            $referer = wp_get_referer() ?: home_url('/');
            $msg = is_string($result) && !empty($result) ? $result : 'Impossibile scaricare la tessera.';
            wp_safe_redirect(add_query_arg('asi_error', rawurlencode($msg), $referer));
            exit;
        }
    }

    public function pulsante_asinazionale() {
        ob_start();
        include ASINAZIONALE_PLUGIN_PATH . 'templates/shortcode.php';
        return ob_get_clean();
    }
}