<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class API
{
    private $tipoMsg;
    private $arrResposta;
    private $apiKey;
    private $proxy = 'true';

    public function __construct($tipo, $key)
    {
        $this->apiKey = $key;
        $this->tipoMsg = $tipo;
    }

    private function getDate($d)
    {
        $ano = substr($d, 0, 4);
        $mes = substr($d, 4, 2) - 1;
        $dia = substr($d, 6, 2);
        return new DateTime($ano . "-" . $mes . "-" . $dia);
    }

    public function getProxy()
    {
        return $this->proxy;
    }

    private function incDay($d)
    {
        return $d->modify('+1 day');
    }

    private function fillZero($n)
    {
        return intval($n) < 10 ? "0" . $n : "" . $n;
    }

    private function dateToStr($d)
    {
        return $d->format('Ymd');
    }

    private function intervalToArray($di, $df, $urlData)
    {
        function makeArrayDiDf($di, $df, $arr)
        {
            $arrUrl = [];
            $idi = $di;
            $arrLength = count($arr);
            for ($idx = 0; $idx < $arrLength; $idx++) {
                if ($idx == 0) {
                    $idi = $di;
                    $idf = $arr[1] . "00";
                } else {
                    $idi = $arr[$idx] . "00";
                    if ($idx < $arrLength - 1) {
                        $idf = $arr[$idx + 1] . "00";
                    } else {
                        $idf = $df;
                    }
                }
                $arrUrl[] = ['di' => $idi, 'df' => $idf];
            }
            return $arrUrl;
        }

        function makeArrayUrl($arrDias, $urlData)
        {
            $r = [];
            foreach ($arrDias as $e) {
                $r[] = "/WebServiceOPMET/getMetarOPMET.php?local=" . $urlData['localidade'] .
                    "&msg=" . $urlData['tipoMsg'] . "&data_ini=" . $e['di'] .
                    "&data_fim=" . $e['df'] . "&proxy=" . $urlData['proxy'];
            }
            return $r;
        }

        $r = [];
        $xdf = $df;
        $df = $this->getDate($df);
        $next = $this->getDate($di);
        $r[] = $this->dateToStr(new DateTime($next->format('Y-m-d')));
        $next = $this->getDate($this->incDay($next));

        while ($next <= $df) {
            $r[] = $this->dateToStr(new DateTime($next->format('Y-m-d')));
            $next = $this->getDate($this->incDay($next));
        }
        if (count($r) == 1) {
            $r[] = $this->dateToStr(new DateTime($df->format('Y-m-d')));
        }

        return makeArrayUrl(makeArrayDiDf($di, $xdf, $r), $urlData);
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getOpmet($callBack, $localidade, $datai, $dataf)
    {
        function getPagesO($arrDias, $pg, $callBack, &$arr, $obj)
        {
            for ($i = $pg; $i < count($arrDias); $i++) {
                $response = file_get_contents($arrDias[$i]);
                $data = trim($response);
                $r = explode("=", $data);
                array_splice($r, count($r) - 1, 1);
                foreach ($r as $mens) {
                    $arr[] = ['mens' => str_replace("\n", '', $mens) . "="];
                }
                if (strpos($data, "*#*Erro na consulta") !== false) {
                    $i--;
                }
            }
            $obj->arrResposta = array_merge($obj->arrResposta, [$arr]);
            $callBack($arr, $obj->tipoMsg);
        }

        function getUrlPage($arrDias, $pg)
        {
            return $arrDias[$pg];
        }

        $dataIni = $datai;
        $dataFim = $dataf;
        $resp = [];

        $arrDias = $this->intervalToArray($datai, $dataf, ['localidade' => $localidade, 'tipoMsg' => strtolower($this->tipoMsg), 'proxy' => $this->getProxy()]);
        $numPages = count($arrDias);
        $paginaAtual = 0;
        $urlBase = getUrlPage($arrDias, $paginaAtual);
        $response = @file_get_contents($urlBase);
        if ($response !== false) {
            $this->arrResposta = [];
            $r = explode("=", $response);
            array_splice($r, count($r) - 1, 1);
            foreach ($r as $mens) {
                $resp[] = ['mens' => str_replace("\n", '', $mens) . "="];
            }

            if ($numPages > 1) {
                $paginaAtual++;
                $urlPage = getUrlPage($arrDias, $paginaAtual);
                getPagesO($arrDias, $paginaAtual, $callBack, $resp, $this);
            } else {
                $this->arrResposta = array_merge($this->arrResposta, [$resp]);
                $callBack($resp, $this->tipoMsg);
            }
        }
    }

    public function getRedemet($callBack, $localidade, $datai, $dataf)
    {
        function getPagesR($url, $lastPage, $callBack, &$arg, $obj)
        {
            for ($i = 2; $i <= $lastPage; $i++) {
                $response = file_get_contents($url . $i);
                $data = json_decode($response, true);
                foreach ($data['data']['data'] as $mens) {
                    $arg[] = $mens;
                }
            }
            $obj->arrResposta = array_merge($obj->arrResposta, [$arg]);
            $callBack($arg, $obj->tipoMsg);
        }

        function getUrlPages($url)
        {
            return explode("?page=", $url)[0] . "page=";
        }

        $dataIni = $datai;
        $dataFim = $dataf;
        $resp = [];

        $urlBase = "https://api-redemet.decea.mil.br/mensagens/" . strtolower($this->tipoMsg) . "/" . $localidade . "?api_key=" . $this->getApiKey() . "&data_ini=" . $dataIni . "&data_fim=" . $dataFim;
        $response = @file_get_contents($urlBase);
        if ($response !== false) {
            $this->arrResposta = [];
            $data = json_decode($response, true);
            foreach ($data['data']['data'] as $mens) {
                $resp[] = $mens;
            }

            if ($data['data']['last_page'] > 1) {
                $urlPages = getUrlPages($data['data']['next_page_url']);
                getPagesR($urlBase . $urlPages, $data['data']['last_page'], $callBack, $resp, $this);
            } else {
                $this->arrResposta = array_merge($this->arrResposta, [$resp]);
                $callBack($resp, $this->tipoMsg);
            }
        }
    }

    public function getResposta()
    {
        if (is_array($this->arrResposta)) {
            return array_shift($this->arrResposta);
        } else {
            return false;
        }
    }

    public function arraySize($arr)
    {
        return count($arr);
    }
}

class METAR extends API
{
    public function __construct($apiKey)
    {
        parent::__construct("METAR", $apiKey);
    }

    public function getLocalidade($metar)
    {
        $campos = explode(" ", $metar);
        $idxLoc = 1;
        if (strpos($metar, " COR ") !== false) {
            $idxLoc = $idxLoc + 1;
        }
        return $campos[$idxLoc];
    }
}

class SIGMET extends API
{
    public function __construct($apiKey)
    {
        parent::__construct("SIGMET", $apiKey);
    }
}

class AIRMET extends API
{
    public function __construct($apiKey)
    {
        parent::__construct("AIRMET", $apiKey);
    }
}

class TAF extends API
{
    public function __construct($apiKey)
    {
        parent::__construct("TAF", $apiKey);
    }

    public function getLocalidade($taf)
    {
        $campos = explode(" ", $taf);
        $idxLoc = 1;
        if (strpos($taf, " COR ") !== false) {
            $idxLoc = $idxLoc + 1;
        }
        return $campos[$idxLoc];
    }
}

class GAMET extends API
{
    public function __construct($apiKey)
    {
        parent::__construct("GAMET", $apiKey);
    }

    public function getLocalidade($gamet)
    {
        $campos = explode(" ", $gamet);
        $idxLoc = 1;
        if (strpos($gamet, " COR ") !== false) {
            $idxLoc = $idxLoc + 1;
        }
        return $campos[$idxLoc];
    }
}

class AVISO extends API
{
    public function __construct($apiKey, $tipo = "AVISO")
    {
        parent::__construct($tipo, $apiKey);
    }
}

$trataResposta = function ($r, $tipo) {
    $msgs = "";
    $cont = 0;
    $contAMD = 0;
    $contCOR = 0;
    $contWS = 0;
    $txtWS = "";

    $r = array_map(function ($i) {
        return $i['mens'];
    }, $r);
    $r = array_unique($r);

    foreach ($r as $m) {
        $msgs .= $m . "<br>";
        if (strpos($m, $tipo . " AMD ") !== false) {
            $contAMD++;
        }
        if (strpos($m, $tipo . " WS WRNG ") !== false) {
            $contWS++;
        }
        if (strpos($m, " COR ") !== false) {
            $contCOR++;
        }
        $cont++;
    }

    if (!isset($_GET["exibirMensagens"]) || $_GET["exibirMensagens"] !== "on") {
        $msgs = "";
    } else {
        $msgs .= '<br>';
    }
    if ($contWS > 0) {
        $txtWS = "<br>Total de Mensagens CORTANTE DE VENTO: " . $contWS;
    }
    $tipo = $tipo == "AVISO_AERODROMO" ? "AVISO" : $tipo;
    $tipo = $tipo == "AVISO_CORTANTE_VENTO" ? "AVISOWS" : $tipo;
    $txtTipo = $tipo == "METAR" ? "METAR / SPECI" : $tipo;
    $txtTipo = $tipo == "AVISO" ? "AVISO DE AERODROMO" : $txtTipo;
    $txtTipo = $tipo == "AVISOWS" ? "AVISO DE CORTANTE DE VENTO" : $txtTipo;

    echo "<div id='resposta" . $tipo . "'>" . $msgs . "Total de Mensagens " . $txtTipo . ": " . $cont .
        "<br>Total de Mensagens " . $txtTipo . " AMD: " . $contAMD .
        "<br>Total de Mensagens " . $txtTipo . " COR: " . $contCOR .
        "<br>" . $txtWS . "<br><br></div>";

    $mesAno = $_GET["mesRef"];
    saveToFile($txtTipo . "_" . $mesAno, "tipo\tMês Ref.\tAMD\tCOR\tTOTAL\n" . $txtTipo . "\t" . $mesAno . "\t" . $contAMD . "\t" . $contCOR . "\t" . $cont);
    echo "<script>$.LoadingOverlay('hide');</script>";
};

function consultar($api = 1, $tipoMsg = false, $localidades = false)
{
    $data_ini = $_GET["dataIni"];
    $data_fin = $_GET["dataFin"];

    if (!$localidades) {
        $localidades = str_replace(' ', '', $_GET["localidades"]);
    }

    if (!$tipoMsg) {
        $tipoMsg = strtoupper($_GET["tipoMsg"]);
    } else {
        $tipoMsg = strtoupper($tipoMsg);
    }

    if ($tipoMsg == "METAR") {
        $consulta = new METAR($_GET["apiKey"]);
    } else if ($tipoMsg == "TAF") {
        $consulta = new TAF($_GET["apiKey"]);
    } else if ($tipoMsg == "GAMET") {
        $consulta = new GAMET($_GET["apiKey"]);
    } else if ($tipoMsg == "SIGMET") {
        $consulta = new SIGMET($_GET["apiKey"]);
    } else if ($tipoMsg == "AIRMET") {
        $consulta = new AIRMET($_GET["apiKey"]);
    } else if ($tipoMsg == "AVISOAD") {
        if ($api == 1) {
            $consulta = new AVISO($_GET["apiKey"], "AVISO");
        } else {
            $consulta = new AVISO($_GET["apiKey"], "AVISO_AERODROMO");
        }
    } else if ($tipoMsg == "AVISOWS") {
        if ($api == 1) {
            $consulta = new AVISO($_GET["apiKey"], "AVISO");
        } else {
            $consulta = new AVISO($_GET["apiKey"], "AVISO_CORTANTE_VENTO");
        }
    }

    //echo "<script>$.LoadingOverlay('show');</script>";

    if ($api == 2) {
        $consulta->getOpmet($trataResposta, $localidades, $data_ini, $data_fin);
    } else {
        $consulta->getRedemet($trataResposta, $localidades, $data_ini, $data_fin);
    }
}

function consultaProdutividade($api = 2)
{
    consultar($api, "avisoad", "SBGL,SBEG,SBPA,SBRE,SBGR,SBBR");
    consultar($api, "avisows", "SBGL,SBEG,SBPA,SBRE,SBGR,SBBR");
    consultar($api, "sigmet", "SBAZ,SBBS,SBRE,SBAO,SBCW");
    consultar($api, "airmet", "SBAZ,SBBS,SBRE,SBAO,SBCW");
    consultar($api, "taf", "SBPA,SBPK,SBCO,SBSM,SBBG,SBNM,SBUG,SBPF,SBCX,SWKQ,SBCT,SBFI,SBBI,SBYS,SBAF,SBSC,SBGW,SBAN,SBPG,SNCP,SBFL,SBNF,SBJV,SBCH,SBJA,SBMN,SBCC,SBGP,SBUF,SBLJ");
    consultar($api, "gamet", "SBAZ,SBBS,SBRE,SBAO,SBCW");
}

function saveToFile($tipoMsg, $texto)
{
    $filename = $tipoMsg . '.csv';
    file_put_contents($filename, $texto);
    // Implemente aqui a lógica de salvar o arquivo em um local desejado no servidor.
    // Por exemplo, você pode usar a função file_put_contents para salvar o conteúdo em um arquivo no servidor.
}

consultaProdutividade(2);
