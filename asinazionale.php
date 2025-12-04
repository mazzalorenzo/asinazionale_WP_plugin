<?php

/**
 * Plugin Name: Asi Nazionale
 * Description: Un plugin per integrare il sistema di tesseramento di Asi Nazionale con WordPress e permettere agli atleti di scaricare la propria tessera semplicemente inserendo il proprio codice fiscale. Per configurarlo basta inserire le credenziali di accesso al portale di Asi Nazionale nelle impostazioni del plugin.
 * Version: 1.0.0
 * Author: Lorenzo Mazza
 * Author URI: https://mazzalorenzo.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asinazionale
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
const ASINAZIONALE_LOGIN_URL = 'http://tesseramento.asinazionale.it/node?destination=node';
const ASINAZIONALE_SEARCH_URL = 'http://tesseramento.asinazionale.it/dt_tesserati.php';
const ASINAZIONALE_STAMPA_FOGLIO_URL = 'http://tesseramento.asinazionale.it/tessera01.php';
const ASINAZIONALE_STAMPA_FRONTE_RETRO_URL = 'http://tesseramento.asinazionale.it/tessera02.php';
const ASINAZIONALE_STAMPA_CARD_URL = 'http://tesseramento.asinazionale.it/tessera03.php';
const ASINAZIONALE_EXPORT_URL = 'http://tesseramento.asinazionale.it/exportcsv.php';


/**
 * Return configured credentials for ASI Nazionale.
 * Priority: options (plugin settings) -> constants defined in wp-config.php -> empty
 * @return array ['user'=>string, 'pass'=>string]
 */
function asinazionale_check_credentials($user, $pass) {
    if (empty($user) || empty($pass)) {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { if(window.asinazionaleShowError) window.asinazionaleShowError('Errore di configurazione: credenziali ASI Nazionale non impostate. Contatta l\\'amministratore.'); });</script>";
        error_log('Asinazionale: credenziali non configurate.');
        return new WP_Error('config_error', 'Credenziali ASI Nazionale non impostate');
    } else {
        return true;
    }
}

function asinazionale($cf){
    // Alternative using PHP curl directly for exact curl behavior (like bash curl)
    if ( !function_exists( 'curl_init' ) ) {
        return 'cURL not available on this server';
    }
    $user = get_option('asinazionale_user', '');
    $pass = get_option('asinazionale_pass', '');
    $credentials_check = asinazionale_check_credentials($user, $pass);
    if( is_wp_error($credentials_check) ) {
        return;
    }
    else {
        $ch = setup_ch();
        $login_result = asinazionale_login($ch, $user, $pass);
        if (!$login_result) {
            // Login failed, error already handled in asinazionale_login
            curl_close($ch);
            return;
        }
        $search_response = json_decode(asinazionale_search($ch, $user, $cf));
        if($search_response -> iTotalRecords == 1){
            // don't output HTML/JS here; it will break PDF headers. Log for debugging instead
            error_log('Tessera trovata, scaricamento PDF...');
            $id_tessera = $search_response -> data[0] -> DT_RowId;
            asinazionale_tessera_download($ch, $user, $pass, $id_tessera);
        } elseif ($search_response -> iTotalRecords > 1) {
            // Avoid sending alerts which break binary response
            error_log('Errore: più di una tessera trovata per questo codice fiscale.');
            echo "<script>document.addEventListener('DOMContentLoaded', function() { if(window.asinazionaleShowError) window.asinazionaleShowError('Errore: più di una tessera trovata per questo codice fiscale.'); });</script>";
        } else {
            error_log('Errore: nessuna tessera trovata per questo codice fiscale.');
            echo "<script>document.addEventListener('DOMContentLoaded', function() { if(window.asinazionaleShowError) window.asinazionaleShowError('Errore: nessuna tessera trovata per questo codice fiscale.'); });</script>";
        }
        curl_close($ch);
        return "Login Response:\n";
    }
}


function setup_ch() {
	$cookie_jar = sys_get_temp_dir() . '';

	$ch = curl_init();
	// Common cURL setup
    curl_setopt_array( $ch, array(
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 5,
        // enable cookie engine: read and write cookies to the same temporary file
        CURLOPT_COOKIEJAR => $cookie_jar,
        CURLOPT_COOKIEFILE => $cookie_jar,
		CURLOPT_USERAGENT => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0',
	));

	return $ch;
}

function asinazionale_login_attempt($ch, $user, $pass) {
    $postdata = 'name=' . urlencode($user) . '&pass=' . urlencode($pass) . '&op=Accedi&form_id=user_login_block';

    curl_setopt_array( $ch, array(
        CURLOPT_URL => ASINAZIONALE_LOGIN_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postdata,
        CURLOPT_HTTPHEADER => array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language: it,it-IT;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
            'Cache-Control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen( $postdata ),
            'Origin: http://tesseramento.asinazionale.it',
            'Pragma: no-cache',
            'Referer: http://tesseramento.asinazionale.it/node?destination=node',
            'Upgrade-Insecure-Requests: 1'
        )
    ));

    $response = curl_exec( $ch );
    $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    $error = curl_error( $ch );
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error
    ];
}

function asinazionale_login_attempt_result($response, $http_code, $error) {
    // consider success when no curl error and HTTP 2xx/3xx with some response
    if (empty($error) && $http_code >= 200 && $http_code < 400 && !empty($response)) {
        // Check remote page for login validation error: input#edit-name with class "error"
        // If username or password are wrong, the login page is re-displayed with that input marked as error
        $has_remote_error = preg_match('/<input[^>]*id=["\']edit-name["\'][^>]*class=["\'][^"\']*error[^"\']*["\'][^>]*>/i', $response);
        if ($has_remote_error) {
            error_log(sprintf('Asinazionale login attempt %d: remote login returned validation error (input#edit-name has class error).', $i));
            return new WP_Error('auth_error', 'Credenziali non valide');
            // treat as failure and retry
        } else {
            echo "<script>console.log('Login successful on attempt $i, HTTP code: ' + $http_code)</script>";
            return array('success' => true, 'response' => $response);
        }
    } else {
        return new WP_Error('http_error', 'Errore di connessione o HTTP');
    }
}

function asinazionale_login($ch, $user, $pass) {

    $attempts = 10;
    $delay_us = 100000; // 0.1 second

    for ($i = 1; $i <= $attempts; $i++) {
        $attempt = asinazionale_login_attempt($ch, $user, $pass);
        $attemp_result = asinazionale_login_attempt_result($attempt['response'], $attempt['http_code'], $attempt['error']);
        if (!is_wp_error($attempt_result)) {
            return true;
        } else {
            // log attempt
            $error_message = $attemp_result -> get_error_message();
            error_log(sprintf('Asinazionale login attempt %d failed: error=%s', $i, $error_message));
        }

        // log attempt
        error_log(sprintf('Asinazionale login attempt %d failed: http_code=%s error=%s', $i, $http_code, $error ?: 'none'));

        if ($i < $attempts) {
            usleep($delay_us);
        }
    }
    if (is_wp_error($attempt_result)) {
        $error_message = $attemp_result -> get_error_message();
        echo "<script>document.addEventListener('DOMContentLoaded', function() { if(window.asinazionaleShowError) window.asinazionaleShowError($error); });</script>";
        return false;
    } else {
        return true;
    }
}

function asinazionale_search($ch, $user, $cf) {
    $postdata = "draw=2&columns%5B0%5D%5Bdata%5D=stagione&columns%5B0%5D%5Bname%5D=&columns%5B0%5D%5Bsearchable%5D=true&columns%5B0%5D%5Borderable%5D=true&columns%5B0%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B0%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B1%5D%5Bdata%5D=nr_tessera&columns%5B1%5D%5Bname%5D=&columns%5B1%5D%5Bsearchable%5D=true&columns%5B1%5D%5Borderable%5D=true&columns%5B1%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B1%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B2%5D%5Bdata%5D=desPers&columns%5B2%5D%5Bname%5D=&columns%5B2%5D%5Bsearchable%5D=true&columns%5B2%5D%5Borderable%5D=true&columns%5B2%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B2%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B3%5D%5Bdata%5D=tipo_tessera&columns%5B3%5D%5Bname%5D=&columns%5B3%5D%5Bsearchable%5D=true&columns%5B3%5D%5Borderable%5D=true&columns%5B3%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B3%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B4%5D%5Bdata%5D=qualdis&columns%5B4%5D%5Bname%5D=&columns%5B4%5D%5Bsearchable%5D=true&columns%5B4%5D%5Borderable%5D=true&columns%5B4%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B4%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B5%5D%5Bdata%5D=denominazioneCOM&columns%5B5%5D%5Bname%5D=&columns%5B5%5D%5Bsearchable%5D=true&columns%5B5%5D%5Borderable%5D=true&columns%5B5%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B5%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B6%5D%5Bdata%5D=des_soc&columns%5B6%5D%5Bname%5D=&columns%5B6%5D%5Bsearchable%5D=true&columns%5B6%5D%5Borderable%5D=true&columns%5B6%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B6%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B7%5D%5Bdata%5D=inizioTess&columns%5B7%5D%5Bname%5D=&columns%5B7%5D%5Bsearchable%5D=true&columns%5B7%5D%5Borderable%5D=true&columns%5B7%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B7%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B8%5D%5Bdata%5D=fineTess&columns%5B8%5D%5Bname%5D=&columns%5B8%5D%5Bsearchable%5D=true&columns%5B8%5D%5Borderable%5D=true&columns%5B8%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B8%5D%5Bsearch%5D%5Bregex%5D=false&order%5B0%5D%5Bcolumn%5D=0&order%5B0%5D%5Bdir%5D=asc&start=0&length=10&search%5Bvalue%5D=&search%5Bregex%5D=false&stagioneSportiva_fltr=&societa_fltr=&referente_fltr=&codAff_fltr=" . urlencode($user) . "&regione=0&provincia=&comune=&cognome_fltr=&nome_fltr=&cod_fisc_fltr=$cf&age=-&comitato_fltr=0&nrTessMin_fltr=&nrTessMag_fltr=&dataAtMax=&dataScMax=&dataAtMin=&dataScMin=&disciplina=0&tipo_tessera=0";


    curl_setopt_array( $ch, array(
		CURLOPT_URL => ASINAZIONALE_SEARCH_URL,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $postdata,
		CURLOPT_HTTPHEADER => array(
			'Accept: application/json, text/javascript, */*; q=0.01',
			'Accept-Language: it,it-IT;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
			'Cache-Control: no-cache',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
#			'Content-Length: ' . strlen( $postdata ),
			'Origin: http://tesseramento.asinazionale.it',
			'Pragma: no-cache',
			'Referer: http://tesseramento.asinazionale.it/tessere',
			'Upgrade-Insecure-Requests: 1',
			'X-Requested-With: XMLHttpRequest'
		)
	));

    // Ricevi il contenuto
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Errore cURL: " . curl_error($ch);
        curl_close($ch);
        return;
    }
    echo "<script>console.log('Search response: ', $response)</script>";
    return $response;
}

function asinazionale_tessera_download($ch, $user, $pass, $id_tessera) {
	// Implementa la funzione di download della tessera qui


    curl_setopt($ch, CURLOPT_URL, ASINAZIONALE_STAMPA_FOGLIO_URL);
    curl_setopt($ch, CURLOPT_POST, true);

    // Body esattamente come nel tuo curl
    $postfields = "isFSN=0&isCP=0&isSOC=1&isSOC=0&username_utente=" . urlencode($user) . "&bt=fd&btn=&idLotto=idLotto&id_tessera=$id_tessera&canwrite=0&societa_fltr=&codAff_fltr=" . urlencode($user) . "&referente_fltr=&regione=0&cognome_fltr=&nome_fltr=&age=-&cod_fisc_fltr=&discipline_fltr=0&nrTessMag_fltr=&nrTessMin_fltr=&tipotesseraAsi=0&dataAtMax=&dataAtMin=&dataScMax=&dataScMin=&stagioneSportiva_fltr=&TesseratiDT_length=10";
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
        "Accept-Language: it,it-IT;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6",
        "Cache-Control: no-cache",
        "Connection: keep-alive",
        "Content-Type: application/x-www-form-urlencoded",
        "Origin: http://tesseramento.asinazionale.it",
        "Pragma: no-cache",
        "Referer: http://tesseramento.asinazionale.it/tessere",
        "Upgrade-Insecure-Requests: 1",
    ));


    // Ricevi il contenuto
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Errore cURL: " . curl_error($ch);
        curl_close($ch);
        return;
    }
	
    // Clear any buffered output before sending binary PDF
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Scarica il PDF
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=\"tessera.pdf\"");
    header("Content-Length: " . strlen($response));
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: 0");

    echo $response;
    wp_die();
}



function asinazionale_shortcode() {
    if (isset($_POST['asinazionale']) && !empty($_POST['cf'])) {
        $cf = sanitize_text_field($_POST['cf']);
        asinazionale($cf);
    }

    return '<div class="container">
                <form method="post" class="asinazionale">
                    <div class="mb-3">
                        <label for="cf" class="form-label"></label>
                        <input type="text" name="cf" id="cf" class="form-control" placeholder="Codice Fiscale" required>
                        <small id="cfHelp" class="form-text text-muted">Inserisci il tuo codice fiscale</small>

                    </div>
                    <div class="">
                        <button type="submit" name="asinazionale" class="btn btn-primary">Scarica Tessera</button>
                    </div>
                </form>
            </div>';
}


add_shortcode('pulsante_asinazionale', 'asinazionale_shortcode');

function asinazionale_enqueue_assets() {
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
#    wp_enqueue_style('asinazionale-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.0.0');
#    wp_enqueue_script('asinazionale-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), '1.0.0', true);
}

add_action('wp_enqueue_scripts', 'asinazionale_enqueue_assets');

/**
 * Admin settings: register options and add settings page under Settings.
 */
add_action('admin_menu', 'asinazionale_admin_menu');
add_action('admin_init', 'asinazionale_admin_settings');

function asinazionale_admin_menu() {
    add_options_page(
        'ASI Nazionale',
        'ASI Nazionale',
        'manage_options',
        'asinazionale-settings',
        'asinazionale_settings_page'
    );
}

function asinazionale_admin_settings() {
    register_setting('asinazionale_settings_group', 'asinazionale_user', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));
    register_setting('asinazionale_settings_group', 'asinazionale_pass', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));
}

function asinazionale_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>ASI Nazionale — Impostazioni</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('asinazionale_settings_group');
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="asinazionale_user">Username</label></th>
                    <td><input name="asinazionale_user" type="text" id="asinazionale_user" value="<?php echo esc_attr(get_option('asinazionale_user', '')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="asinazionale_pass">Password</label></th>
                    <td><input name="asinazionale_pass" type="password" id="asinazionale_pass" value="<?php echo esc_attr(get_option('asinazionale_pass', '')); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}