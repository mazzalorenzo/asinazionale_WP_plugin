<?php

namespace Asinazionale\Website;

class Login extends Website {
    private const LOGIN_URL = 'http://tesseramento.asinazionale.it/node?destination=node';


    private $attempt = 1; // The current attempt number
    private $attempts = 10; // Maximum number of attempts
    private $delay_us = 100000; // 0.1 second

    private $login_response = null;

    public function __construct($attempts = 10, $delay_us = 100000) {
        parent::__construct();
        $this->attempts = $attempts;
        $this->delay_us = $delay_us;
        // initialize runtime values that cannot be used as property defaults
    }

    /**
     * Perform login with retries
     * @return true on success, false on failure
     */
    function login() {
        $attempt_result = false;
        while ($this->attempt <= $this->attempts && $attempt_result !== true) {
            $this->login_attempt();
            $attempt_result = $this->login_attempt_result();

            // increment attempt counter after checking
            $this->attempt++;

            if ($this->attempt <= $this->attempts) {
                usleep($this->delay_us);
            }
        }

        if (is_wp_error($attempt_result)) {
            $error = $attempt_result->get_error();
            $error_message = $attempt_result->get_error_message();
            #$js_msg = json_encode($error_message);
            do_action('asinazionale_show_error', $error, $error_message);
            #echo "<script>document.addEventListener('DOMContentLoaded', function() { if(window.asinazionaleShowError) window.asinazionaleShowError($js_msg); });</script>";
            return false;
        } else {
            return true;
        }
    }

    function login_attempt() {
        $postdata = 'name=' . urlencode(self::$user) . '&pass=' . urlencode(self::$pass) . '&op=Accedi&form_id=user_login_block';

        curl_setopt_array( self::$ch, array(
            CURLOPT_URL => self::LOGIN_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postdata,
            CURLOPT_HTTPHEADER => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language: it,it-IT;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
                'Cache-Control: no-cache',
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen( $postdata ),
                'Origin: ' . self::WEBSITE_URL,
                'Pragma: no-cache',
                'Referer: ' . self::LOGIN_URL,
                'Upgrade-Insecure-Requests: 1'
            )
        ));

        $this->login_response = curl_exec( self::$ch );

    }

    /**
     * Check the result of the last login attempt
     * @return true on success, WP_Error on failure
     */
    function login_attempt_result() {
        $http_code = curl_getinfo( self::$ch, CURLINFO_HTTP_CODE );
        $error = curl_error( self::$ch );

        // consider success when no curl error and HTTP 2xx/3xx with some response
        if (empty($error) && $http_code >= 200 && $http_code < 400 && !empty($this->login_response)) {
            // Check remote page for login validation error: input#edit-name with class "error"
            // If username or password are wrong, the login page is re-displayed with that input marked as error
            $has_auth_error = preg_match('/<input[^>]*id=["\']edit-name["\'][^>]*class=["\'][^"\']*error[^"\']*["\'][^>]*>/i', $this->login_response);
            if ($has_auth_error) {
                $error = 'Auth error';
                $error_message = sprintf('Asinazionale login attempt %d: Invalid credentials', $this->attempt);
                do_action('asinazionale_log', $error_message);
                return new \WP_Error($error, $error_message);
            } else {
                $log_message = sprintf('Login successful on attempt %d. HTTP code: %d', $this->attempt, $http_code);
                do_action('asinazionale_log', $log_message);
                return true;
            }
        } else {
            $error = 'Connection error';
            $error_message = sprintf('Asinazionale login attempt %d: Connection error. HTTP code: %d', $this->attempt, $http_code);
            $user_message = 'Errore di connessione o HTTP';
//            do_action('asinazionale_show_error', $user_message );
            do_action('asinazionale_log', $error_message );
            return new \WP_Error($error, $error_message);
        }
    }
}