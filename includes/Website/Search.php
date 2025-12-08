<?php

namespace Asinazionale\Website;

class Search extends Website {
    private const SEARCH_URL = 'http://tesseramento.asinazionale.it/dt_tesserati.php';
    private $search_response;
    private $cf = '';
    private $stagione = '';
    private $societa = '';
    private $referente = '';
    private $codAff = '';
    private $regione = '';
    private $provincia = '';
    private $comune = '';
    private $cognome = '';
    private $nome = '';
    private $cod_fisc = '';
    private $age = '';
    private $comitato = '';
    private $nrTessMin = '';
    private $nrTessMag = '';
    private $dataAtMax = '';
    private $dataAtMin = '';
    private $dataScMax = '';
    private $dataScMin = '';
    private $disciplina = '';
    private $tipo_tessera = '';


    public function __construct($cf) {
        $this->cf = $cf;
        $postdata = "draw=2&columns%5B0%5D%5Bdata%5D=stagione&columns%5B0%5D%5Bname%5D=&columns%5B0%5D%5Bsearchable%5D=true&columns%5B0%5D%5Borderable%5D=true&columns%5B0%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B0%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B1%5D%5Bdata%5D=nr_tessera&columns%5B1%5D%5Bname%5D=&columns%5B1%5D%5Bsearchable%5D=true&columns%5B1%5D%5Borderable%5D=true&columns%5B1%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B1%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B2%5D%5Bdata%5D=desPers&columns%5B2%5D%5Bname%5D=&columns%5B2%5D%5Bsearchable%5D=true&columns%5B2%5D%5Borderable%5D=true&columns%5B2%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B2%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B3%5D%5Bdata%5D=tipo_tessera&columns%5B3%5D%5Bname%5D=&columns%5B3%5D%5Bsearchable%5D=true&columns%5B3%5D%5Borderable%5D=true&columns%5B3%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B3%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B4%5D%5Bdata%5D=qualdis&columns%5B4%5D%5Bname%5D=&columns%5B4%5D%5Bsearchable%5D=true&columns%5B4%5D%5Borderable%5D=true&columns%5B4%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B4%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B5%5D%5Bdata%5D=denominazioneCOM&columns%5B5%5D%5Bname%5D=&columns%5B5%5D%5Bsearchable%5D=true&columns%5B5%5D%5Borderable%5D=true&columns%5B5%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B5%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B6%5D%5Bdata%5D=des_soc&columns%5B6%5D%5Bname%5D=&columns%5B6%5D%5Bsearchable%5D=true&columns%5B6%5D%5Borderable%5D=true&columns%5B6%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B6%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B7%5D%5Bdata%5D=inizioTess&columns%5B7%5D%5Bname%5D=&columns%5B7%5D%5Bsearchable%5D=true&columns%5B7%5D%5Borderable%5D=true&columns%5B7%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B7%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B8%5D%5Bdata%5D=fineTess&columns%5B8%5D%5Bname%5D=&columns%5B8%5D%5Bsearchable%5D=true&columns%5B8%5D%5Borderable%5D=true&columns%5B8%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B8%5D%5Bsearch%5D%5Bregex%5D=false&order%5B0%5D%5Bcolumn%5D=0&order%5B0%5D%5Bdir%5D=asc&start=0&length=10&search%5Bvalue%5D=&search%5Bregex%5D=false&stagioneSportiva_fltr=&societa_fltr=&referente_fltr=&codAff_fltr=" . urlencode(self::$user) . "&regione=0&provincia=&comune=&cognome_fltr=&nome_fltr=&cod_fisc_fltr=$cf&age=-&comitato_fltr=0&nrTessMin_fltr=&nrTessMag_fltr=&dataAtMax=&dataScMax=&dataAtMin=&dataScMin=&disciplina=0&tipo_tessera=0";

        curl_setopt_array( self::$ch, array(
            CURLOPT_URL => self::SEARCH_URL,
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
        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);

        $this->search_response = curl_exec(self::$ch);

        if (curl_errno(self::$ch)) {
            echo "Errore cURL: " . curl_error(self::$ch);
            curl_close(self::$ch);
            return;
        }
        echo "<script>console.log('Search response: ', $this->search_response)</script>";
        return json_decode($this->search_response);
    }

    function search_results() {
        // Implementa la funzione di gestione del risultato della ricerca qui
        return json_decode($this->search_response) -> iTotalRecords;
    }

    function get_id_tessere() {
        // Implementa la funzione per ottenere le tessere trovate qui
#        foreach ($search_response -> data as $tessera) {
#            error_log('Tessera trovata: ID ' . $tessera -> DT_RowId);
#        }
    }

    function get_id_tessera() {
        
        // Implementa la funzione per ottenere le tessere trovate qui
        return json_decode($this->search_response) -> data[0] -> DT_RowId;
    }


}