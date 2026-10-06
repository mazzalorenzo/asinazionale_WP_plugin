<?php

namespace Asinazionale\Website;

class Search extends Website {
    private const SEARCH_URL = 'https://tesseramento.asinazionale.it/dt_tesserati.php';
    private $search_response = null;
    private $cf = '';

    public function __construct($cf) {
        parent::__construct();
        $this->cf = $cf;

        $params = array(
            'draw' => 2,
            'start' => 0,
            'length' => 10,
            'search[value]' => '',
            'search[regex]' => 'false',
            'stagioneSportiva_fltr' => '',
            'societa_fltr' => '',
            'referente_fltr' => '',
            'codAff_fltr' => self::$user,
            'regione' => 0,
            'provincia' => '',
            'comune' => '',
            'cognome_fltr' => '',
            'nome_fltr' => '',
            'cod_fisc_fltr' => $this->cf,
            'age' => '-',
            'comitato_fltr' => 0,
            'nrTessMin_fltr' => '',
            'nrTessMag_fltr' => '',
            'dataAtMax' => '',
            'dataScMax' => '',
            'dataAtMin' => '',
            'dataScMin' => '',
            'disciplina' => 0,
            'tipo_tessera' => 0,
        );

        $columns = array('stagione','nr_tessera','desPers','tipo_tessera','qualdis','denominazioneCOM','des_soc','inizioTess','fineTess');
        foreach ($columns as $i => $name) {
            $params["columns[$i][data]"] = $name;
            $params["columns[$i][name]"] = '';
            $params["columns[$i][searchable]"] = 'true';
            $params["columns[$i][orderable]"] = 'true';
            $params["columns[$i][search][value]"] = '';
            $params["columns[$i][search][regex]"] = 'false';
        }
        $params['order[0][column]'] = 0;
        $params['order[0][dir]'] = 'asc';

        curl_setopt_array(self::$ch, array(
            CURLOPT_URL => self::SEARCH_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Language: it-IT,it;q=0.9,en;q=0.7',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Origin: https://tesseramento.asinazionale.it',
                'Referer: https://tesseramento.asinazionale.it/tessere',
                'X-Requested-With: XMLHttpRequest'
            )
        ));

        $this->search_response = curl_exec(self::$ch);
    }

    public function search_results() {
        if (curl_errno(self::$ch)) {
            $error_msg = curl_error(self::$ch);
            do_action('asinazionale_log', 'Errore cURL ricerca: ' . $error_msg);
            return new \WP_Error('curl_error', 'Errore di connessione durante la ricerca.');
        }

        $json = json_decode($this->search_response);
        if (!is_object($json) || json_last_error() !== JSON_ERROR_NONE) {
            do_action('asinazionale_log', 'Risposta ricerca non JSON');
            return new \WP_Error('invalid_json', 'Risposta non valida dal portale ASI.');
        }

        if (isset($json->iTotalRecords)) {
            return (int)$json->iTotalRecords;
        } elseif (isset($json->recordsFiltered)) {
            return (int)$json->recordsFiltered;
        }

        return 0;
    }

    public function get_id_tessera() {
        $json = json_decode($this->search_response);
        if (isset($json->data[0]->DT_RowId)) {
            return $json->data[0]->DT_RowId;
        }
        return null;
    }
}