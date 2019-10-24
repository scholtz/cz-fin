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


$table = "data_mfcr_dic_bad_core";

//$result = file_get_contents("result.html");
//if(!$result){

//http://adisrws.mfcr.cz/adistc/axis2/services/rozhraniCRPDPH.rozhraniCRPDPHSOAP?wsdl

$mySOAP = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
<soapenv:Body>
<SeznamNespolehlivyPlatceRequest xmlns="http://adis.mfcr.cz/rozhraniCRPDPH/">
</SeznamNespolehlivyPlatceRequest>
</soapenv:Body>
</soapenv:Envelope>';

  // The URL to POST to
  $url = "http://adisrws.mfcr.cz/adistc/axis2/services/rozhraniCRPDPH.rozhraniCRPDPHSOAP";

  // The value for the SOAPAction: header
  $action = "http://adis.mfcr.cz/rozhraniCRPDPH/getSeznamNespolehlivyPlatce";

  // Get the SOAP data into a string, I am using HEREDOC syntax
  // but how you do this is irrelevant, the point is just get the
  // body of the request into a string


  // The HTTP headers for the request (based on image above)
  $headers = array(
    'Content-Type: text/xml; charset=utf-8',
    'Content-Length: '.strlen($mySOAP),
    'SOAPAction: '.$action
  );

  // Build the cURL session
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $mySOAP);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  // Send the request and check the response
  if (($result = curl_exec($ch)) === FALSE) {
    die('cURL error: '.curl_error($ch)."<br />\n");
  } else {
    echo "Success!\n";
  }
  curl_close($ch);

  // Handle the response from a successful request
//  file_put_contents("result.html",$result);
  
//}
  
if(strlen($result) < 100000){
    echo "sluzba asi nefunguje: ".strlen($result)."\n";
    Cron::end();
    exit;
}

$dom = new \DomDocument();
@$dom->loadxml($result);  
$dom->save("test01.xml");
$xpath = new \DomXpath($dom);

$current = [];

$xpath->registerNamespace('mfcr', "http://adis.mfcr.cz/rozhraniCRPDPH/");
$ii = 0;
$nodes = $xpath->query("//mfcr:statusPlatceDPH");
$cc = $nodes->length;
foreach($nodes as $node){

    $ii++;
    if($ii%10==0) echo ".";
    if($ii%1000==0) echo "$ii/$cc/".date("c")."\n";

    $balancer->wait();
    $update = [];
    if($node->hasAttributes()){
        foreach($node->attributes as $attr){
            $update[Texts::clear($attr->nodeName)] = $attr->nodeValue;
        }
    }
    
    if($update["dic"]){
        $current[$update["dic"]] = true;
        DB::u($table,md5($update["dic"]."-".$update["datumzverejneninespolehlivosti"]),$update);
    }
}

$res = DB::qb($table);
$ii=0;
echo "\nidem skontrolovat ci niektora spolocnost je uz spolahliva..\n";
$cc = DB::num_rows($res);
while($row=DB::f($res)){
    
    $ii++;
    if($ii%10==0) echo ".";
    if($ii%1000==0) echo "$ii/$cc/".date("c")."\n";

    $balancer->wait();
    if(isset($current[$row["dic"]])){
        continue;
    }
    if($row["datumzukonceninespolehlivosti"]){
        continue;
    }
    // we found dic which is no longer bad
    
    DB::u($table,$row["id2"],["datumzukonceninespolehlivosti"=>date("Y-m-d")]);
}


echo "\nfinished ".date("c")."\n";
Cron::end();
