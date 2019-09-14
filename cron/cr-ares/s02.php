<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$pokracuj_za = "04753933";$working =false;


$f = '/cron2/apps/arescz/ares_vreo_all.tar.gz';
if(filemtime($f) < time() - 3600*24*7){
    echo "Downloading:";
    $hSource = fopen('http://wwwinfo.mfcr.cz/ares/ares_vreo_all.tar.gz', 'r');
    $hDest = fopen($f, 'w+');
    if(!$hDest){
        echo "unable to create file.\n";exit;
    }
    while (!feof($hSource)) {
        $chunk = fread($hSource, 1024*16);
        fwrite($hDest, $chunk);
        echo ".";
    }
    fclose($hSource);
    fclose($hDest);    
}
$to = "/dev/shm/tmp/arescz/";
mkdir($to,0777,true);
echo "\nDownloaded. Extracting to $to";
$cmd = "tar -xvzf \"$f\" -C \"$to\"";
echo "\nCommand:\n$cmd\n";
var_dump(exec($cmd));

$processor = new AresProcessor();
$dir = $to."VYSTUP/DATA/";
$files = scandir($dir);
$cc = count($files);
foreach($files as $file){
    if(substr($file,-4) == ".xml"){
        if(strpos($file,$pokracuj_za) !== false){
            $working = true;
        }
        if($working){
            $file = file_get_contents($dir.$file);
            $processor->processXML($file);
        }
        $ii++;
        if($ii%10==0) echo ".";
        if($ii%1000==0) echo "$ii/$cc/".date("c")."\n";
    }
}


$cmd = "rm -rf \"$to\"";
echo "\nCommand:\n$cmd\n";
var_dump(exec($cmd));
/*
echo "dropping tables ".date("c");
DB::query("drop table if exists data_arescz_adr_core");
DB::query("drop table if exists data_arescz_statorg_core");
DB::query("drop table if exists data_arescz_fosoba_core");
DB::query("drop table if exists data_arescz_jednanie_core");
DB::query("drop table if exists data_arescz_cinnost_core");
/**/
/*
//$archive = new PharData('phar:///cron2/apps/arescz/ares_vreo_all.tar/VYSTUP/DATA');
$processor = new AresProcessor();
$tar_object = new Archive_Tar('/cron2/apps/arescz/ares_vreo_all.tar', true);
$ii = 0;

if (($v_list  =  $tar_object->listContent()) != 0) {
    $cc = sizeof($v_list);
    for ($i=0; $i<$cc; $i++) {
        if(substr($v_list[$i]['filename'],-4) == ".xml"){
            if(strpos($v_list[$i]['filename'],$pokracuj_za) !== false){
                $working = true;
            }
            if($working){
                $file = $tar_object->extractInString($v_list[$i]['filename']);
                //echo $v_list[$i]['filename'].": ".strlen($file)."\n";
                $processor->processXML($file);
            }
            $ii++;
            if($ii%10==0) echo ".";
            if($ii%1000==0) echo "$ii/$cc/".date("c")."\n";
        }
   }
}

/**/

//$files = ["00000337.xml","00000515.xml","86652257.xml"];


class AresProcessor{
    function processXML($data){
        $doc = new DOMDocument();
        @$doc->loadXML($data);
        if(!$doc) return;
        $xpath = new DOMXpath($doc);
        if(!$xpath) return;
        $vypisNodes = $xpath->query("//are:Vypis_VREO");
        for($i = $vypisNodes->length - 1;$i>=0;$i--){
            $node = $vypisNodes->item($i);
            $firma = [];
            foreach($xpath->query("are:Zakladni_udaje/node()",$node) as $zaklNode){
                if($zaklNode->childNodes == null){continue;}
                
                if($zaklNode->childNodes->length == 1){
                    $firma[Texts::clear(str_replace("are:","",$zaklNode->nodeName))] = $this->processText($zaklNode->nodeValue);
                }
            }
            $firma["clear"] = Texts::clear($firma["obchodnifirma"]);
            //var_dump(":".$firma["clear"]);
            foreach($xpath->query("are:Zakladni_udaje/node()",$node) as $zaklNode){
                if($zaklNode->childNodes == null){continue;}
                if($zaklNode->childNodes->length == 1){continue;}
            
                // cinnosti alebo sidlo
                if($zaklNode->nodeName == "are:Sidlo"){
                    if($adrid = $this->spracujAdresu($zaklNode,$xpath)){
                        $firma["sidlo"] = $adrid;
                    }
                }
                
                if($zaklNode->nodeName == "are:Cinnosti"){
                    foreach($xpath->query("are:PredmetPodnikani/are:Text",$zaklNode) as $cinnostNode){
                        $cinnost = $cinnostNode->nodeValue;
                        $cinnost = str_replace("\n"," ",$cinnost);
                        $cinnost = str_replace("  "," ",$cinnost);
                        $cinnost = trim($cinnost);
                        $clear = substr(Texts::clear($cinnost),0,250);
                        //var_dump($clear);
                        if(is_numeric($clear)) continue;
                        $updateCinnost = ["ico"=>$firma["ico"],"clear"=>$clear,"cinnost"=>$cinnost,"zapis"=>$firma["datumzapisu"],"vymaz"=>$firma["datumvymazu"]];
                        $updateCinnost["firma"] = $firma["obchodnifirma"];
                        $updateCinnost["firmaclear"] = $firma["clear"];;
                        $id = md5($clear.$firma["ico"].$firma["datumzapisu"].$firma["obchodnifirma"]);

                        DB::u("data_arescz_cinnost_core",$id,$updateCinnost);
                    }
                    
                }
            
            }
            
            
            foreach($xpath->query("are:Statutarni_organ",$node) as $statNode){
                $dza = $statNode->getAttribute("dza");
                $dvy = $statNode->getAttribute("dvy");
                $nazevNode = $xpath->query("are:Nazev",$statNode)->item(0);
                $nazev = "";
                if($nazevNode){
                    $nazev = $this->processText($nazevNode->nodeValue);
                }
                $clear = Texts::clear($nazev);
                
                $id = md5($clear.$firma["ico"].$dza.$firma["clear"]);            
                $updatestat = ["ico"=>$firma["ico"],"clear"=>$clear,"name"=>$nazev,"dza"=>$dza,"dvy"=>$dvy];
                $updatestat["firma"] = $firma["obchodnifirma"];
                $updatestat["firmaclear"] = $firma["clear"];;

                //var_dump($updatestat["firmaclear"]);
                DB::u("data_arescz_statorg_core",$id,$updatestat);
                
                foreach($statNode->childNodes as $statChildNode){
                    if($statChildNode->nodeName == "#text") continue;
                    switch($statChildNode->nodeName){
                        case "are:ZpusobJednani":
                            $updatezj = [];
                            $dzaJedn = $statChildNode->getAttribute("dza");
                            $dvyJedn = $statChildNode->getAttribute("dvy");
                            if($textNode = $xpath->query("are:Text",$statChildNode)->item(0)){
                                $text = $this->processText($textNode->nodeValue);
                                $updatezj["statorg"] = $id;
                                $updatezj["ico"] = $firma["ico"];
                                $updatezj["clear"] = Texts::clear($text);
                                $updatezj["text"] = $text;
                                $updatezj["firma"] = $firma["obchodnifirma"];
                                $updatezj["firmaclear"] = $firma["clear"];;
                                $updatezj["dza"] = $dzaJedn;
                                $updatezj["dvy"] = $dvyJedn;
                                $idjedn = md5($updatezj["statorg"].$updatezj["ico"].$updatezj["clear"].$updatezj["dza"].$firma["clear"]);
                                DB::u("data_arescz_jednanie_core",$idjedn,$updatezj);
                            }                        
                        break;
                        case "are:Clen":
                            $updateclen = [];
                            $dzaJedn = $statChildNode->getAttribute("dza");
                            $dvyJedn = $statChildNode->getAttribute("dvy");
                            $updateosob = [];
                            
                            if($textNode = $xpath->query("are:funkce/are:nazev",$statChildNode)->item(0)){
                                $text = $this->processText($textNode->nodeValue);
                                $updateosob["statorg"] = $id;
                                $updateosob["ico"] = $firma["ico"];
                                $updateosob["funkceclear"] = Texts::clear($text);
                                $updateosob["funkce"] = $text;
                                $updateosob["firma"] = $firma["obchodnifirma"];
                                $updateosob["firmaclear"] = $firma["clear"];;
                                
                                $updateosob["dza"] = $dzaJedn;
                                $updateosob["dvy"] = $dvyJedn;
                                
                                $fosobaNodes = $xpath->query("are:fosoba|are:posoba",$statChildNode);
                                if($fosobaNode = $fosobaNodes->item(0)){
                                    
                                    $idosob = md5($updateosob["statorg"].$updateosob["ico"].$updateosob["clear"].$updateosob["dza"].$updateosob["firma"]);
                                    foreach($fosobaNode->childNodes as $el){
                                        if(substr($el->nodeName,0,4) == "are:"){
                                            if($el->nodeName == "are:adresa"){
                                                if($adrid = $this->spracujAdresu($el,$xpath)){
                                                    $updateosob["adresa"] = $adrid;
                                                }
                                                continue;
                                            }
                                            
                                            $k = str_replace("are:","",$el->nodeName);
                                            $updateosob[Texts::clear($k)] = $this->processText($el->nodeValue);
                                            $idosob = md5($idosob.$updateosob[$k]);
                                        }
                                    }
                                    
                                    if($updateosob["jmeno"] || $updateosob["prijmeni"]){
                                        $updateosob["nameclear"] = Texts::clear($updateosob["jmeno"]." ".$updateosob["prijmeni"]);
                                    }
                                    
                                    $typ= $fosobaNode->nodeName == "are:fosoba" ? "fosoba" : "posoba";
                                    
                                    DB::u("data_arescz_${typ}_core",$idosob,$updateosob);
                                    continue;
                                }
                            }
                        break;
                    }
                }
            }
            if(!$firma["ico"]) continue;
            DB::u("data_arescz_company_core",$firma["ico"],$firma);
        }
    }
        
    function processText($text){
        if(!$text) return "";
        
        $ret = "";
        foreach(explode("\n",$text) as $line){
            $line = trim($line);
            
            $ret.=$line;
            if(substr($line,-1) == "-"){
                // do not add space
                $ret = substr($ret,0,-1);
            }else{
                $ret.=" ";
            }
        }
        
        return trim($ret);
    }

    function spracujAdresu($zaklNode,$xpath){
        if(!$zaklNode) return false;
        $adrCode = $xpath->query("are:ruianKod",$zaklNode)->item(0);
        $id = "";
        $addr = [];
        foreach($zaklNode->childNodes as $adrNode){
            if($adrNode->nodeName == "#text"){continue;}
            $addr[Texts::clear(str_replace("are:","",$adrNode->nodeName))] = $adrNode->nodeValue;
        }
        asort($addr);
        foreach($addr as $k=>$v){
            if($k == "ruianKod") continue;
            $id = md5($id.$k.$v);
        }
        $addr["address"] = trim(trim(trim(trim($addr["ulice"]." ".$addr["cislotxt"]).", ".$addr["psc"])." ".$addr["obec"]),',');
        $addr["clear"] = Texts::clear($addr["address"]);
        if(!$addr["clear"]) $addr["clear"] = Texts::clear($addr["text"]);
        if(!$addr["text"]) $addr["text"] = $addr["address"];
        DB::u("data_arescz_adr_core",$id,$addr);
        return $id;
    }
}





Cron::end();
