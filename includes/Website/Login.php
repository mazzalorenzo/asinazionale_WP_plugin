<?php

namespace Asinazionale\Website;

class Login extends Website {
    private const LOGIN_URL = 'https://tesseramento.asinazionale.it/node?destination=node';

    private $attempt = 1;
    private $attempts = 10;
    private $delay_us = 100000; // 0.1 second

    private $login_response = null;

    public function __construct($attempts = 10, $delay_us = 100000) {
        parent::__construct();
        $this->attempts = $attempts;
        $this->delay_us = $delay_us;
    }

    /**
     * Perform login with retries
     * @return true|\WP_Error true on success, WP_Error on failure
     */
    public function login() {
        $attempt_result = false;
        while ($this->attempt <= $this->attempts && $attempt_result !== true) {
            $this->login_attempt();
            $attempt_result = $this->login_attempt_result();

            $this->attempt++;

            if ($this->attempt <= $this->attempts && $attempt_result !== true) {
                usleep($this->delay_us);
            }
        }

        if (is_wp_error($attempt_result)) {
            $error = $attempt_result->get_error_code();
            $error_message = $attempt_result->get_error_message();
            do_action('asinazionale_show_error', $error_message, $error);
            return $attempt_result;
        } else {
            return true;
        }
    }

    public function login_attempt() {
        $postdata = http_build_query(array(
            'name' => self::$user,
            'pass' => self::$pass,
            'op' => 'Accedi',
            'form_id' => 'user_login_block'
        ), '', '&');

        curl_setopt_array(self::$ch, array(
            CURLOPT_URL => self::LOGIN_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postdata,
            CURLOPT_HTTPHEADER => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: it-IT,it;q=0.9,en;q=0.7',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://tesseramento.asinazionale.it',
                'Referer: https://tesseramento.asinazionale.it/node?destination=node',
            )
        ));

        $this->login_response = curl_exec(self::$ch);
    }

    /**
     * Check the result of the last login attempt
     * @return true|\WP_Error true on success, WP_Error on failure
     */
    public function login_attempt_result() {
        $http_code = (int)curl_getinfo(self::$ch, CURLINFO_HTTP_CODE);
        $error = curl_error(self::$ch);

        if (empty($error) && $http_code >= 200 && $http_code < 400 && !empty($this->login_response)) {
            $failure_patterns = array(
                '/id=["\']edit-name["\'][^>]*class=["\'][^"\']*error/i',
                '/nome utente o password[^<]*(?:non valid|errat)/i',
                '/unrecognized username or password/i',
            );
            foreach ($failure_patterns as $pattern) {
                if (preg_match($pattern, $this->login_response)) {
                    $error_code = 'auth_error';
                    $error_message = 'Login ASI non riuscito: controllare username e password.';
                    do_action('asinazionale_log', sprintf('Attempt %d failed: invalid credentials', $this->attempt));
                    return new \WP_Error($error_code, $error_message);
                }
            }

            if (preg_match('/form_id["\']?\s*(?:value=|=)["\']user_login_block/i', $this->login_response) &&
                preg_match('/id=["\']edit-pass["\']/i', $this->login_response)) {
                $error_code = 'auth_error';
                $error_message = 'Il portale ASI ha riproposto la pagina di login. Accesso non confermato.';
                do_action('asinazionale_log', sprintf('Attempt %d failed: login form re-proposed', $this->attempt));
                return new \WP_Error($error_code, $error_message);
            }

            $log_message = sprintf('Login successful on attempt %d. HTTP code: %d', $this->attempt, $http_code);
            do_action('asinazionale_log', $log_message);
            return true;
        } else {
            $error_code = 'connection_error';
            $error_message = sprintf('Errore di connessione al portale ASI (tentativo %d). Codice HTTP: %d', $this->attempt, $http_code);
            do_action('asinazionale_log', $error_message);
            return new \WP_Error($error_code, $error_message);
        }
    }
}