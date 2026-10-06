<?php

namespace Asinazionale;

class Shortcode {

    public function __construct() {
        add_shortcode('pulsante_asinazionale', [$this, 'pulsante_asinazionale']);
        add_action('template_redirect', [$this, 'handle_download_request'], 1);
    }

    private function get_return_url() {
        $referer = wp_get_referer();
        if ($referer) {
            return $referer;
        }
        if (!empty($_POST['asi_current_url'])) {
            $path = wp_unslash($_POST['asi_current_url']);
            if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
                return esc_url_raw($path);
            }
            return home_url($path);
        }
        if (!empty($_SERVER['REQUEST_URI'])) {
            return home_url(wp_unslash($_SERVER['REQUEST_URI']));
        }
        return home_url('/');
    }


    public function handle_download_request() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['cf'])) {
            return;
        }

        $cf = strtoupper(preg_replace('/[^A-Z0-9]/i', '', wp_unslash($_POST['cf'])));
        if (empty($cf) || strlen($cf) < 11 || strlen($cf) > 16) {
            wp_safe_redirect(add_query_arg('asi_error', rawurlencode('Codice fiscale non valido.'), $this->get_return_url()));
            exit;
        }

        $website = new \Asinazionale\Website\Website();
        $result = $website->asinazionale($cf);

        if (is_wp_error($result)) {
            wp_safe_redirect(add_query_arg('asi_error', rawurlencode($result->get_error_message()), $this->get_return_url()));
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
            $msg = is_string($result) && !empty($result) ? $result : 'Impossibile scaricare la tessera.';
            wp_safe_redirect(add_query_arg('asi_error', rawurlencode($msg), $this->get_return_url()));
            exit;
        }
    }

    public function pulsante_asinazionale() {
        ob_start();
        include ASINAZIONALE_PLUGIN_PATH . 'templates/shortcode.php';
        return ob_get_clean();
    }
}
