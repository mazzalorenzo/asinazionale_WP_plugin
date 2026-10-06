<?php

namespace Asinazionale\Website;

class Website {
    protected const WEBSITE_URL = 'https://tesseramento.asinazionale.it/';
    protected static $ch = null;
    protected static $user = null;
    protected static $pass = null;
    protected static $cookie_jar = null;

    public function __construct() {
        if (self::$user === null) {
            self::$user = trim((string)get_option('asinazionale_user', ''));
        }
        if (self::$pass === null) {
            self::$pass = (string)get_option('asinazionale_pass', '');
        }

        if (empty(self::$cookie_jar) || !file_exists(self::$cookie_jar)) {
            self::$cookie_jar = tempnam(sys_get_temp_dir(), 'asi_cookie_');
        }

        if (self::$ch === null || !is_resource(self::$ch) && !(self::$ch instanceof \CurlHandle)) {
            self::$ch = curl_init();
            self::init();
        }
    }

    public function __destruct() {
        // Cleanup resources if needed
    }

    public static function cleanup() {
        if (self::$ch) {
            if (is_resource(self::$ch) || self::$ch instanceof \CurlHandle) {
                curl_close(self::$ch);
            }
            self::$ch = null;
        }
        if (self::$cookie_jar && file_exists(self::$cookie_jar)) {
            @unlink(self::$cookie_jar);
            self::$cookie_jar = null;
        }
    }

    /**
     * Return configured credentials for ASI Nazionale.
     * @return bool|\WP_Error True if credentials are set, WP_Error otherwise.
     */
    protected function check_credentials() {
        if (empty(self::$user) || empty(self::$pass)) {
            $error = 'config_error';
            $message = "Credenziali ASI Nazionale non impostate. Contattare l'amministratore del sito";
            do_action('asinazionale_show_error', $message);
            do_action('asinazionale_log', $message);
            return new \WP_Error($error, $message);
        } else {
            return true;
        }
    }
    
    public static function init() {
        if (!self::$ch) return;

        curl_setopt_array(self::$ch, array(
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/142.0 Safari/537.36',
            CURLOPT_COOKIEJAR => self::$cookie_jar,
            CURLOPT_COOKIEFILE => self::$cookie_jar,
            CURLOPT_ENCODING => '',
        ));
    }

    public function asinazionale($cf) {
        if (!function_exists('curl_init')) {
            return new \WP_Error('curl_missing', 'cURL non è disponibile su questo server.');
        }

        $credentials_check = $this->check_credentials();
        if (is_wp_error($credentials_check)) {
            self::cleanup();
            return $credentials_check;
        }

        $login = new Login();
        $login_result = $login->login();
        if (is_wp_error($login_result)) {
            self::cleanup();
            return $login_result;
        }

        $search = new Search($cf);
        $search_results = $search->search_results();

        if (is_wp_error($search_results)) {
            self::cleanup();
            return $search_results;
        }

        if ($search_results == 1) {
            $id_tessera = $search->get_id_tessera();
            if (!$id_tessera) {
                self::cleanup();
                return new \WP_Error('search_error', 'Tessera trovata ma identificativo non valido.');
            }
            $download = new Download();
            $pdf = $download->tessera_download($id_tessera);
            self::cleanup();
            return $pdf;
        } elseif ($search_results > 1) {
            self::cleanup();
            return new \WP_Error('search_error', 'Errore: più di una tessera trovata per questo codice fiscale.');
        } else {
            self::cleanup();
            return new \WP_Error('search_error', 'Nessuna tessera trovata per questo codice fiscale.');
        }
    }
}