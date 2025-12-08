<?php

namespace Asinazionale\Website;

class Website {
    protected const WEBSITE_URL = 'http://tesseramento.asinazionale.it/';
    protected static $ch;
    protected static $user;
    protected static $pass;
    protected static $cookie_jar;

    public function __construct() {
        self::$ch = curl_init();
        self::$user = get_option('asinazionale_user', '');
        self::$pass = get_option('asinazionale_pass', '');
        self::$cookie_jar = sys_get_temp_dir() . 'asinazionalecookies.txt';
        self::init();
#        $this->check_credentials();
    }

    function __destruct() {
#        curl_close(self::$ch);
    }

    /**
     * Return configured credentials for ASI Nazionale.
     * Priority: options (plugin settings) -> constants defined in wp-config.php -> empty
     * @return bool|WP_Error True if credentials are set, WP_Error otherwise.
     */
    private function check_credentials() {
        if (empty(self::$user) || empty(self::$pass)) {
            $error = 'config_error';
            $message = "Credenziali ASI Nazionale non impostate. Contattare l\'amministratore del sito";
            do_action('asinazionale_show_error', $message);
            do_action('asinazionale_log', $message);
            return new \WP_Error($error, $message);
        } else {
            return true;
        }
    }
    
    public static function init() {

        // Common cURL setup
        curl_setopt_array( self::$ch, array(
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            // enable cookie engine: read and write cookies to the same temporary file
            CURLOPT_COOKIEJAR => self::$cookie_jar,
            CURLOPT_COOKIEFILE => self::$cookie_jar,
        ));

    }

    function asinazionale($cf){
        // Alternative using PHP curl directly for exact curl behavior (like bash curl)
        if ( !function_exists( 'curl_init' ) ) {
            return 'cURL not available on this server';
        }

        $credentials_check = $this->check_credentials();
        if( is_wp_error($credentials_check) ) {
            return 'Credenziali non valide.';
        }
        else {
            $login = new Login();
            if(!$login->login()) {
                // Login failed, error already handled in asinazionale_login
                return;
            }
            $search = new Search($cf);
            $search_results = $search->search_results();

            if($search_results == 1){
                // don't output HTML/JS here; it will break PDF headers. Log for debugging instead
            #    error_log('Tessera trovata, scaricamento PDF...');                
                $id_tessera = $search->get_id_tessera();
                (new Download)->tessera_download($id_tessera);
            } elseif ($search_results > 1) {
                // Avoid sending alerts which break binary response
                error_log('Errore: più di una tessera trovata per questo codice fiscale.');
                echo "<script>document.addEventListener('DOMContentLoaded', function() { if(window.asinazionaleShowError) window.asinazionaleShowError('Errore: più di una tessera trovata per questo codice fiscale.'); });</script>";
            } else {
                $error = ' Search error';
                $message = 'Nessuna tessera trovata per questo codice fiscale';
                do_action('asinazionale_show_error', $message);
#                error_log('Errore: nessuna tessera trovata per questo codice fiscale.');
#                echo "<script>document.addEventListener('DOMContentLoaded', function() { if(window.asinazionaleShowError) window.asinazionaleShowError('Errore: nessuna tessera trovata per questo codice fiscale.'); });</script>";
            }
            curl_close(self::$ch);
            return "Login Response:\n";
        }
    }

}