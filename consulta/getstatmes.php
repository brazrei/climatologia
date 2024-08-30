<?php
//$resp = file_get_contents('http://10.52.231.31/produtividade/teste1/index.php?mesref="07-2021"&tipomsg="TAF"');

$url = 'http://10.52.231.31/produtividade/teste1/index.php?mesref="06-2021"&tipomsg="TAF"';

echo file_get_contents($url);
/*$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, false);
$data = curl_exec($curl);
curl_close($curl);

var_dump($data);

*/

//ob_end_clean();
//$resp = $resp + "";
//echo $data;
//echo file_get_contents('csv/TAF_06-2021.csv');
