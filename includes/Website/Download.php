<?php

namespace Asinazionale\Website;

class Download extends Website {
    private const STAMPA_FOGLIO_URL = 'https://tesseramento.asinazionale.it/tessera01.php';
    private const STAMPA_FRONTE_RETRO_URL = 'https://tesseramento.asinazionale.it/tessera02.php';
    private const STAMPA_CARD_URL = 'https://tesseramento.asinazionale.it/tessera03.php';

    public function tessera_download($id_tessera) {
        $params = array(
            'isFSN' => 0,
            'isCP' => 0,
            'isSOC' => 0,
            'username_utente' => self::$user,
            'bt' => 'fd',
            'btn' => '',
            'idLotto' => 'idLotto',
            'id_tessera' => $id_tessera,
            'canwrite' => 0,
            'societa_fltr' => '',
            'codAff_fltr' => self::$user,
            'referente_fltr' => '',
            'regione' => 0,
            'cognome_fltr' => '',
            'nome_fltr' => '',
            'age' => '-',
            'cod_fisc_fltr' => '',
            'discipline_fltr' => 0,
            'nrTessMag_fltr' => '',
            'nrTessMin_fltr' => '',
            'tipotesseraAsi' => 0,
            'dataAtMax' => '',
            'dataAtMin' => '',
            'dataScMax' => '',
            'dataScMin' => '',
            'stagioneSportiva_fltr' => '',
            'TesseratiDT_length' => 10,
        );

        curl_setopt_array(self::$ch, array(
            CURLOPT_URL => self::STAMPA_FOGLIO_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/pdf,text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: it-IT,it;q=0.9,en;q=0.7',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://tesseramento.asinazionale.it',
                'Referer: https://tesseramento.asinazionale.it/tessere',
            )
        ));

        $response = curl_exec(self::$ch);

        if (curl_errno(self::$ch)) {
            $error_msg = curl_error(self::$ch);
            do_action('asinazionale_log', 'Errore cURL download: ' . $error_msg);
            return new \WP_Error('curl_error', 'Errore durante il download del PDF.');
        }

        $is_pdf = (strncmp($response, '%PDF-', 5) === 0);
        if (!$is_pdf) {
            do_action('asinazionale_log', 'Risposta download non PDF');
            return new \WP_Error('invalid_pdf', 'Il portale ASI non ha restituito un file PDF valido.');
        }

        return $response;
    }
}