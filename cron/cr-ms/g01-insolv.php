<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
use AsyncWeb\DataMining\WebMining;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$balancer = new \AsyncWeb\System\CPU\LoadBalancer(0.2,5000,0.3,50000,0.5,2000000);


$table = "data_mscr_insolv_webs";
$datumZverejneniUdalosti = "";
$idPodnetu = 17478836;

$work = true;
$ii = 0;
while($work){
    
    $balancer->wait();
    
    $ii++;
    if($ii%1==0) echo ".";
    if($ii%100==0) echo "\n$ii/$idPodnetu/$datumZverejneniUdalosti/".date("c")."";
    
    $work = false;
    $path = "https://isir.justice.cz:8443/isir_public_ws/IsirWsPublicService?idPodnetu=".$idPodnetu;
    
    
    
    $content = Page::load($path,$table);
    if(strpos($content,"Systém je nedostupný")){
        $content = "";
    }
//    file_put_contents("tmp01.html",$content);
    if(!$content){
        
        $mySOAP = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope
        xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
        xmlns:typ="http://isirpublicws.cca.cz/types/">
        <soapenv:Header/>
        <soapenv:Body>
        <typ:getIsirWsPublicIdDataRequest>
        <idPodnetu>'.$idPodnetu.'</idPodnetu>
        </typ:getIsirWsPublicIdDataRequest>
        </soapenv:Body>
        </soapenv:Envelope>';

          // The URL to POST to
          $url = "https://isir.justice.cz:8443/isir_public_ws/IsirWsPublicService";

          // The value for the SOAPAction: header
          //$action = "http://adis.mfcr.cz/rozhraniCRPDPH/getSeznamNespolehlivyPlatce";

          // Get the SOAP data into a string, I am using HEREDOC syntax
          // but how you do this is irrelevant, the point is just get the
          // body of the request into a string


          // The HTTP headers for the request (based on image above)
          $headers = array(
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: '.strlen($mySOAP),
        //    'SOAPAction: '.$action
          );

          // Build the cURL session
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_POST, TRUE);
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $mySOAP);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
          echo "\ndownloading: $path ";
          // Send the request and check the response
          if (($content = curl_exec($ch)) === FALSE) {
            die('cURL error: '.curl_error($ch)."<br />\n");
          } else {
            echo "done ".strlen($content)." $datumZverejneniUdalosti/".date("c");
          }
          curl_close($ch);

          file_put_contents("result.txt",$content);
        
        Page::save($path,$content,$table);
        sleep(1);
    }
    if($content){
        $xml = new \DomDocument();
        @$xml->loadxml($content);
        $xpath=new \DomXpath($xml);
        if($xpath){
            $ids = $xpath->query("//id");
            if($ids->length > 0){
                $work = true;
                $idPodnetu = $ids->item($ids->length-1)->nodeValue;
            }
            $ids = $xpath->query("//datumZverejneniUdalosti");
            if($ids->length > 0){
                $datumZverejneniUdalosti = $ids->item($ids->length-1)->nodeValue;
            }
        }
        // Handle the response from a successful request
        //  file_put_contents("result.html",$result);
        //}

    }
    if(!$idPodnetu){
        exit;
    }
}

var_dump($idPodnetu);