<?php

namespace Asinazionale\Website;

class Download extends Website {
    private const STAMPA_FOGLIO_URL = 'http://tesseramento.asinazionale.it/tessera01.php';
    private const STAMPA_FRONTE_RETRO_URL = 'http://tesseramento.asinazionale.it/tessera02.php';
    private const STAMPA_CARD_URL = 'http://tesseramento.asinazionale.it/tessera03.php';


    function tessera_download($id_tessera) {
        // Implementa la funzione di download della tessera qui

        // Body esattamente come nel tuo curl
        $postfields = "isFSN=0&isCP=0&isSOC=1&isSOC=0&username_utente=" . urlencode(self::$user) . "&bt=fd&btn=&idLotto=idLotto&id_tessera=$id_tessera&canwrite=0&societa_fltr=&codAff_fltr=" . urlencode(self::$user) . "&referente_fltr=&regione=0&cognome_fltr=&nome_fltr=&age=-&cod_fisc_fltr=&discipline_fltr=0&nrTessMag_fltr=&nrTessMin_fltr=&tipotesseraAsi=0&dataAtMax=&dataAtMin=&dataScMax=&dataScMin=&stagioneSportiva_fltr=&TesseratiDT_length=10";

        // Headers
        curl_setopt_array( self::$ch, array(
            CURLOPT_URL => self::STAMPA_FOGLIO_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_COOKIEFILE => self::$cookie_jar,
            CURLOPT_HTTPHEADER => array(
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
                "Accept-Language: it,it-IT;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Type: application/x-www-form-urlencoded",
                "Origin: http://tesseramento.asinazionale.it",
                "Pragma: no-cache",
                "Referer: http://tesseramento.asinazionale.it/tessere",
                "Upgrade-Insecure-Requests: 1",
            )
        ));

        // Ricevi il contenuto
        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec(self::$ch);

        if (curl_errno(self::$ch)) {
            echo "Errore cURL: " . curl_error(self::$ch);
            curl_close(self::$ch);
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
        return;
//        wp_die();
    }

    public function download_pdf(){

    }
}