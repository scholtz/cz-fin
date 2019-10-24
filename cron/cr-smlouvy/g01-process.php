<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
require_once("/cron2/settingsCZ.php");

Cron::start(24*3600);


$processor = new Processor();
$processor->downloadIndex();
//$processor->downloadLinkedXmlFiles();
//$processor->processXmlFile("https://data.smlouvy.gov.cz/dump_2018_01.xml");
//$processor->processAllXmlFiles();
class Processor{
    private $fullwebstable = "data_smlouvy_webs";
    public $table = "data_smlouvy_core";
    public $LARGE_FILE_DIR = "/mnt/2tb/cr/opendata/";
    private $balancer;
    private $updated = false;
    public function __construct(){
        $this->balancer = new \AsyncWeb\System\CPU\LoadBalancer(0.2,5000,0.3,50000,0.5,2000000);
    }
    
    public function makeLargeFileName($path){
        return $this->LARGE_FILE_DIR.md5($path).substr($path,-10);
    }
    public function downloadIndex(){
        echo "kontrolujem index z data.smlouvy.gov.cz\n";
        if(Page::downloadWithEtag($path = "https://data.smlouvy.gov.cz/index.xml",$this->fullwebstable,$this->makeLargeFileName($path))){
            echo "$path has been updated\n";
            $this->downloadLinkedXmlFiles();
        }
    }
    public function downloadLinkedXmlFiles(){
        $data = Page::load("https://data.smlouvy.gov.cz/index.xml",$this->fullwebstable);
        
        $dom = new \DomDocument();
        $dom->loadXml($data);
        $xpath = new \DomXpath($dom);
        $xpath->registerNamespace("isrs","http://portal.gov.cz/rejstriky/ISRS/1.2/");
        foreach($xpath->query("//isrs:odkaz") as $node){
            $path = $node->nodeValue;
            var_dump($path);
            echo "kontrolujem $path\n";
            if(Page::downloadWithEtag($path,$this->fullwebstable,$this->makeLargeFileName($path))){
                echo "$path has been updated\n";
                $this->processXmlFile($path);
            }
        }
        if($this->updated){
            $this->copyCoreTableToOutput();
        }
    }
    
    public function processAllXmlFiles(){
        $data = Page::load("https://data.smlouvy.gov.cz/index.xml",$this->fullwebstable);
        
        $dom = new \DomDocument();
        $dom->loadXml($data);
        $xpath = new \DomXpath($dom);
        $xpath->registerNamespace("isrs","http://portal.gov.cz/rejstriky/ISRS/1.2/");
        foreach($xpath->query("//isrs:odkaz") as $node){
            $path = $node->nodeValue;
            var_dump($path);
            echo "processing $path\n";
            $this->processXmlFile($path);
        }
        if($this->updated){
            $this->copyCoreTableToOutput();
        }
    }
    public function copyCoreTableToOutput(){
        $r = DB::gr($table = $this->table);
        if($r){
            
            $rand = rand(10000,99999);
            echo "\ncopying to prod tmp table schema `out`.`${table}_tmp$rand`\n";
            DB::query("CREATE TABLE `out`.`${table}_tmp$rand` LIKE `devcz`.`$table`");
            echo DB::error();
            echo "\ncopying to prod tmp table data\n";
            DB::query("INSERT INTO `out`.`${table}_tmp$rand` SELECT * FROM `devcz`.`$table` where do = 0");
            echo DB::error();
            echo "\ndropping table\n";
            DB::query("drop table `out`.`$table`");
            echo DB::error();
            echo "\nrenaming table `${table}_tmp$rand` TO `out`.`$table`\n";
            DB::query("RENAME TABLE `out`.`${table}_tmp$rand` TO `out`.`$table`");
            echo DB::error();
            echo "DONE";	
        }
    }
    public function processXmlFile($path){
        $this->updated = true;
        $data = Page::load($path,$this->fullwebstable);
        $dom = new \DomDocument();
        var_dump(strlen($data));
        $dom->loadXml($data);
        $xpath = new \DomXpath($dom);
        $xpath->registerNamespace("isrs","http://portal.gov.cz/rejstriky/ISRS/1.2/");
        
        $config = [];
        
        
        
        $config["cols"]["smlouvni-strana-ico"]["type"] = "varchar";
        
        $config["cols"]["smlouva-predmet"]["type"] = "text";
        $config["cols"]["smlouva-hodnotaBezDph"]["type"] = "decimal";
        $config["cols"]["smlouva-hodnotaBezDph"]["before"] = 30;
        $config["cols"]["smlouva-hodnotaBezDph"]["after"] = 6;
        $config["keys"][] = "data-smlouva-predmet-clear";
        $config["keys"][] = "data-date";
        $config["keys"][] = "subjekt-ico";
        $config["keys"][] = "smlouvni-strana-ico";
        $i = 0;
        $nodes = $xpath->query("//isrs:zaznam");
        $cc = $nodes->length;
        foreach($nodes as $node){
            $i++;
            if($i % 10 == 0){echo ".";}
            if($i % 1000 == 0){echo $i."/$cc/".date("c")."\n";}
            $this->balancer->wait();
            
            if($node2 = $xpath->query("isrs:identifikator/isrs:idSmlouvy",$node)->item(0)){
                $update["id-smlouvy"] = $node2->nodeValue;
            }
            if($node2 = $xpath->query("isrs:identifikator/isrs:idVerze",$node)->item(0)){
                $update["id-verze"] = $node2->nodeValue;
                $update["source"] = "https://smlouvy.gov.cz/smlouva/".$node2->nodeValue;
            }
            if($node2 = $xpath->query("isrs:odkaz",$node)->item(0)){
                $update["source"] = $node2->nodeValue;
            }
            
            foreach($xpath->query("isrs:smlouva/*",$node) as $nodeSmlouva){
                if($nodeSmlouva->nodeName == "subjekt"){
                    foreach($nodeSmlouva->childNodes as $nodeSubjekt){
                        $update["subjekt-".$nodeSubjekt->nodeName] = $nodeSubjekt->nodeValue;
                    }
                }else
                if($nodeSmlouva->nodeName == "smluvniStrana"){
                    foreach($nodeSmlouva->childNodes as $nodeStrana){
                        $update["smlouvni-strana-".$nodeStrana->nodeName] = $nodeStrana->nodeValue;
                    }
                }else{
                    $update["smlouva-".$nodeSmlouva->nodeName] = $nodeSmlouva->nodeValue;
                }
            }
            if(!$update["id-verze"]){
                $id = "";
                asort($update);
                foreach($update as $k=>$v){
                    $id = md5("$id-$k-$v");
                }
                $update["id-verze"] = $id;
            }
            if($update["smlouva-predmet"]){
                $update["data-smlouva-predmet-clear"] = substr(\AsyncWeb\Text\Texts::clear($update["smlouva-predmet"]),0,250);
            }
            
            if($update["smlouva-datumUzavreni"]){
                if($t = strtotime($update["smlouva-datumUzavreni"])){
                    if($t > 0){
                        $update["data-date"] = $t;
                    }
                }
            }
            
            DB::u($this->table,$update["id-verze"],$update,$config);
            $config = false;
        }
    }
}
echo "\nDONE ".date("c")."\n";
Cron::end();
