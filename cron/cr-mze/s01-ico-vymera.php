<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c")."\n";

$processor = new MZEDPBProcessor();

$processor->config["data_mze_dpb_core"]["cols"][$colname="vymera"]["before"] = 18;
$processor->config["data_mze_dpb_core"]["cols"][$colname]["after"] = 6;
$processor->config["data_mze_dpb_core"]["cols"][$colname]["type"] = "decimal";
$processor->config["data_mze_dpb_core"]["cols"]["geometrie"]["type"] = "text";
$processor->config["data_mze_dpb_core"]["keys"][] = "iddpb";
$processor->config["data_mze_dpb_core"]["keys"][] = "ico";
$processor->config["data_mze_dpb_core"]["keys"][] = "iduzivatele";
$processor->config["data_mze_dpb_core"]["keys"][] = "stavid";
$processor->config["data_mze_dpb_core"]["keys"][] = "ctverec";
$processor->config["data_mze_dpb_core"]["keys"][] = "kulturaid";
//DB::query("drop table data_mze_dpb_core");
//


$processor->download();

//$processor->processXML(file_get_contents("20190907-CZ0201-600466-DPB-XML-A.xml"));

class MZEDPBProcessor{
    public $config = [];
    public $localName = "data.zip";
    public $localNameCounty = "data-county.zip";
    public $localXml = "data.xml";
    public $webstable = "data_opendata_data_webs";
    private $iter = 0;
    
    public function download(){
        
        if (($handle = fopen("/cron2/apps/cr-opendata/distribuce.csv", "r")) !== FALSE) {
            $n2k = [];
            while (($data = fgetcsv($handle, 16384, ",")) !== FALSE) {$row++;
                if($row==1){
                    
                    foreach($data as $k=>$v){
                        $k2n[$k] = Texts::clear($v);
                        $n2k[Texts::clear($v)] = $k;
                    }
                    
                }else{
                    $update = [];
                    foreach($data as $k=>$v){
                        $update[$k2n[$k]] = $v;
                    }
                    if(strtolower(substr($update["odkazkestazeni"],-14)) != "-dpb-xml-a.zip"){
                        continue;
                    }
                    
                    $path = $update["odkazkestazeni"];
                    $text = Page::load($path,$this->webstable);
                    if(!$text){
                        echo "\nDOWNLOADING $path";
                        $text = file_get_contents($path);
                        Page::save($path,$text,$this->webstable);
                    }
                    echo "\n##################### Processing $path\n";
                    file_put_contents($this->localName,$text);
                    $this->iter = 0;
                    $this->extractZIPAndProcess();
                }
            }
        }
        
    }
    public function extractZIPAndProcess(){
        $zip = new ZipArchive;
        $zip->open($this->localName);
        for($i = 0; $i < $zip->numFiles; $i++)
        {  
            $filename = $zip->getNameIndex($i);
            $fileinfo = pathinfo($filename);
            copy("zip://".$this->localName."#".$filename, $this->localNameCounty);
            $zip2 = new ZipArchive;
            $zip2->open($this->localNameCounty);
            
            for($ii = 0; $ii < $zip2->numFiles; $ii++)
            {  
                $filename2 = $zip2->getNameIndex($ii);
                copy("zip://".$this->localNameCounty."#".$filename2, $this->localXml);
                //echo "\n$filename: ";
                $this->processXML(file_get_contents($this->localXml));
            }
        }
    }
    public function processXML($data){
        $doc = new DOMDocument();
        @$doc->loadXML($data);
        $doc->save("out.xml");
        if(!$doc) return;
        $xpath = new DOMXpath($doc);
        if(!$xpath) return;
        $xpath->registerNameSpace('ns2', 'http://sitewell.cz/lpis/schemas/LPI_GDP01A');
        $i = 0;
        $list = $xpath->query("//ns2:DPB");
        $cc = $list->length;
        //echo "\n";
        foreach($list as $node){

            $this->iter++;
            if($this->iter%10==0) echo ".";
            if($this->iter%1000==0) echo $this->iter."/".date("c")."\n";

            $baseData = [];
            foreach($node->childNodes as $nodeChild){
                if(substr($nodeChild->nodeName,0,4) != "ns2:") continue;
                if($nodeChild->childNodes && $nodeChild->childNodes->length != 1)  continue;
                $col = substr($nodeChild->nodeName,4);
                $col = Texts::clear($col);
                    
                $baseData[$col] = $nodeChild->nodeValue;
                
            }

            if(!$baseData["iddpb"]){
                echo "nemam id uzivatela: ";
                var_dump($user);
                exit;
            }
            $user = [];
            
            foreach($xpath->query("ns2:UZIVATEL",$node) as $uNodeParent){
                if(!$uNodeParent->childNodes) continue;
                foreach($uNodeParent->childNodes as $uNode){
                    if(substr($uNode->nodeName,0,4) != "ns2:") continue;
                    $col = substr($uNode->nodeName,4);
                    $col = Texts::clear($col);
                    
                    $user[$col] = $this->processText($uNode->nodeValue);
                }
            }            
            if(!$user["iduzivatele"]){
                echo "nemam id uzivatela: ";
                var_dump($user);
                exit;
            }
            
            DB::u("data_mze_dpb_users_core",$user["iduzivatele"],$user);
            
            
            $baseData["ico"] = $user["ic"];
            $baseData["iduzivatele"] = $user["iduzivatele"];
            
            
            DB::u("data_mze_dpb_core",$baseData["iddpb"],$baseData,$this->config["data_mze_dpb_core"]);
            
            
            //var_dump($baseData);exit;
            //var_dump($user);exit;

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
