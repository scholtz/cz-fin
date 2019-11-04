<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
require_once("/cron2/settings.php");

Cron::start(24*3600);



$processor = new Processor();
$processor->download();


class Processor{
    private $download = false;
    private $webstable = "data_obce_webs";
    public $LARGE_FILE_DIR = "/mnt/2tb/sk/obce/";
    private $iefile = "/cron2/apps/sk-obce/invalid-emails.txt";
    public $email2obec = [];
    
    public $stats = [
        "errors" => 0,
        "newemails"=>0,
    ];
    public function processExisting(){
        $i = 0;
        foreach(explode("\n",file_get_contents("/cron2/apps/sk-obce/emaily-obci-2.csv")) as $line){
            $i++;
            if($i == 1) continue;// first row contains header
            $row = str_getcsv($line,",");
            if(!$row[0]) continue;
            $this->email2obec[$row[0]] = $row[1];
        }
        $this->email2obec["obec.dubnik@konfer.eu"] = "Dubník";
        $this->email2obec["info@bratislava.sk"] = "Hlavné mesto SR Bratislava";
        $this->email2obec["obec.lubenik@revnet.sk"] = "Lubeník";
    }
    public function makeLargeFileName($path){
        return $this->LARGE_FILE_DIR.md5($path).substr($path,-10);
    }

    public function download(){
        $this->processExisting();
        file_put_contents($this->iefile,"");
        $path = "https://www.slovensko.sk/sk/institucie/miestne-urady/";
        echo "kontrolujem $path\n";
        //Page::$debug = true;
        /*
        if(Page::downloadWithEtag($path,$this->webstable,$this->makeLargeFileName($path))){
            echo "$path has been updated\n";
        }
        /**/
        
        $this->processMain(Page::load($path,$this->webstable));
        $this->stats["emailcount"] = count($this->email2obec);
        $this->createEmailFile();
        $this->showStats();
    }
    
    public function showStats(){
        
        var_dump($this->stats);
        $domains = [];
        foreach($this->email2obec as $k=>$v){
            $domain = substr($k, strpos($k, '@') + 1);
            @$domains[$domain]++;
        }
        arsort($domains);
        echo "domains:\n";
        $i = 0;
        foreach($domains as $k=>$v){$i++;
           echo "$k:$v\n";
           if($i > 20) break;
        }
    }
    
    public function createEmailFile(){
        $out = "";
        foreach($this->email2obec as $k=>$v){
            if($v == "Hlavné mesto SR Bratislava"){
                $v = ["obec"=>"Bratislava - Staré Mesto","okres"=>"Okres Bratislava","kraj"=>"Bratislavský kraj"];
            }
            if(is_array($v)){
                $out.='"'.$k.'","'.$v["obec"].'","'.$v["okres"].'","'.$v["kraj"].'"'."\n";
            }else{
                $out.='"'.$k.'","'.$v.'"'."\n";
            }
        }
        file_put_contents("/ocz/vhosts-jaso/srdcomdoma.sk/web/volby/emaily.csv",$out);
    }
    
    public function processMain($html){
        $dom = new \DomDocument();
        @$dom->loadHTML($html);
        $xpath = new \DomXpath($dom);
        foreach($xpath->query("//li[contains(@id,'liKraj')]/..//a") as $link){
            $l = $link->getAttribute("href");
            if($l == "#") continue;
            if(substr($l,0,1) == "/"){
                $l = "https://www.slovensko.sk".$l;
            }
            $path = $l;
            
            if($this->download){
                echo "kontrolujem $path\n";
                
                if(Page::downloadWithEtag($path,$this->webstable,$this->makeLargeFileName($path))){
                    echo "$path has been updated\n";
                }
            }
            $this->processKraj(Page::load($path,$this->webstable),$link->nodeValue);
                
        }        
    }
    
    public function processKraj($html,$kraj){
        $dom = new \DomDocument();
        @$dom->loadHTML($html);
        $xpath = new \DomXpath($dom);
        foreach($xpath->query("//li[contains(@id,'liOkres')]/..//a") as $link){
            $l = $link->getAttribute("href");
            if($l == "#") continue;
            if(substr($l,0,1) == "/"){
                $l = "https://www.slovensko.sk".$l;
            }
            $path = $l;
            if($this->download){
                echo "kontrolujem $path\n";
                if(Page::downloadWithEtag($path,$this->webstable,$this->makeLargeFileName($path))){
                    echo "$path has been updated\n";
                }
            }
            $this->processOkres(Page::load($path,$this->webstable),$kraj,$link->nodeValue);
        }        
    }
    
    
    public function processOkres($html,$kraj,$okres){
        //file_put_contents("test03.html",$html);exit;
        $dom = new \DomDocument();
        @$dom->loadHTML($html);
        $xpath = new \DomXpath($dom);
        foreach($xpath->query("//div[@class='base-list']//a") as $link){
            $l = $link->getAttribute("href");
            if($l == "#") continue;
            
            $linkName = \AsyncWeb\Text\Texts::clear($link->nodeValue);
            if($linkName != "obecny-urad" && $linkName != "mestsky-urad"){ continue; }
            //echo($linkName."\n");
            
            if(substr($l,0,1) == "/"){
                $l = "https://www.slovensko.sk".$l;
            }
            $path = $l;
            if($this->download){
                echo "stahujem $path\n";
                if(Page::downloadWithEtag($path,$this->webstable,$this->makeLargeFileName($path))){
                    echo "$path has been updated\n";
                }
            }
            $this->processLink(Page::load($path,$this->webstable),$kraj,$okres);
        }        
    }
    
    public function processLink($html,$kraj,$okres){
        // moze to byt info o meste, alebo kontakt na rozne institucie v meste
        
        $ietext = "";
        $html = str_replace("&nbsp;"," ",$html);
        $dom = new \DomDocument();
        @$dom->loadHTML($html);
        $xpath = new \DomXpath($dom);
        $h1 = $xpath->query("//div[@class='mainContent']//h1")->item(0);
        $cityHead = $xpath->query("//h3")->item(0);
        $error = $xpath->query("//p[@class='error']")->item(0);
        if($error){
            if($error->nodeValue == "Požadovanú stránku nie je možné zobraziť.") {
                //echo "E";
                $this->stats["errors"]++;
                return;
            }
            var_dump($error->nodeValue);
        }
        $update = [];
        $update["page"] = $h1->nodeValue;
        $update["city"] = $cityHead->nodeValue;
        $update["okres"] = $okres;
        $update["kraj"] = $kraj;
        
        foreach($xpath->query("//node()[@class='listInfo']") as $node){
            $nameNode = $xpath->query("dt",$node)->item(0);
            
            if(!$nameNode) continue;
            $valueNode = $xpath->query("dd",$node)->item(0);
            if(!$valueNode) continue;
            
            $update[\AsyncWeb\Text\Texts::clear($nameNode->nodeValue)] = $valueNode->nodeValue;
        }

        foreach($xpath->query("//div[@class='mainInfo l-box']") as $node){
            $streetNode = $xpath->query("span[@class='street']",$node)->item(0);
            $cityNode = $xpath->query("span[@class='city']",$node)->item(0);
            $psc = "";
            foreach($xpath->query("span[@class='city']/following-sibling::node()",$node) as $pscNode){
                if(!trim($pscNode->nodeValue)) continue;
                $psc = trim($pscNode->nodeValue);
                break;
            }
            $update["address-street"]= $streetNode->nodeValue;
            $update["address-city"]= $cityNode->nodeValue;
            $update["address-psc"]= $psc;
            
        }
        
        if($update["city"] == "Obecný úrad" && $update["address-city"]){
            $update["city"] .=" ".$update["address-city"];
        }
        $iemail = 1;
        $iweb = 1;
        foreach($xpath->query("//div[@id='institution']//a/@href") as $node){
            if(substr($node->nodeValue,0,11) == "javascript:"){
                
            }elseif(substr($node->nodeValue,0,7) == "mailto:"){
                $sep = ",";
                if(strpos($node->nodeValue,";")) $sep = ";";
                foreach(explode($sep,substr($node->nodeValue,7)) as $value){
                    $value = trim($value);
                    $value = trim($value,"\xC2\xA0");
                    $value = str_replace("%20","",$value);
                    $value = str_replace(" ","",$value);
                    $value = str_replace("í","i",$value);
                    $value = str_replace("ľ","l",$value);
                    $value = str_replace("ocu-porubap.v.@stonline.sk","poruba@poruba.eu",$value);
                    if(!$value) continue;
                    if(!\AsyncWeb\Text\Validate::check("email",$value)){
                        var_dump(file_put_contents($this->iefile,"$value|".$update["city"]."|$okres|$kraj\n",FILE_APPEND));
                        var_dump("email ".$value." nie je platny");
                        if(strpos($value,"dszsu")){
                            file_put_contents("test04.html",$html);
                        }
                        //exit;
                    }else{
                        if(!isset($this->email2obec[$value])){
                            $this->email2obec[$value] = ["obec"=>$update["address-city"],"okres"=>$okres,"kraj"=>$kraj];
                            $this->stats["newemails"]++;
                            //echo "1";
                        }
                    }
                    
                    if($value == "rztz@slovanet.sk"){
                        var_dump($update);
                        file_put_contents("test04.html",$html);
                        exit;
                    }
                    $update["email-$iemail"]= $value;
                    $iemail++;
                }
            }else{
                $update["web-$iweb"]=$node->nodeValue;
                $iweb++;
            }
        }
        $imainInfo = 1;
        $itel = 1;
        $ifax = 1;
        
        foreach($xpath->query("//div[@class='mainInfo']") as $node){
            if($node->childNodes){
                foreach($node->childNodes as $node2){
                    if(!trim($node2->nodeValue)) continue;
                    $update["info-$imainInfo"] = trim($node2->nodeValue);
                    while(strpos($update["info-$imainInfo"],"  ")){
                        $update["info-$imainInfo"] = str_replace("  "," ",$update["info-$imainInfo"]);
                    }
                    if(substr(strtolower($update["info-$imainInfo"]),0,5) == "tel.:"){
                        $update["tel-$itel"] = trim(substr($update["info-$imainInfo"],5));
                        $itel = 1;
                        unset($update["info-$imainInfo"]);
                    }elseif(substr(strtolower($update["info-$imainInfo"]),0,4) == "fax:"){
                        $update["fax-$ifax"] = trim(substr($update["info-$imainInfo"],4));
                        $ifax = 1;
                        unset($update["info-$imainInfo"]);
                    }else{
                    
                        $imainInfo++;
                    }
                }
            }else{
                if(!trim($node->nodeValue)) continue;
                $update["info-$imainInfo"] = trim($node->nodeValue);
                $imainInfo++;
            }
        }
        
        
        foreach($xpath->query("//h4[contains(.,'Úradné hodiny')]/..") as $node){
            for($i = 1;$i<=7;$i++){
                if($d = $xpath->query("//span[contains(@id,'_d".$i."')]")->item(0)){
                    $update["office-hours-$i"] = trim($d->nodeValue);
                }
            }
        }
        
        if($update["page"] == "Inštitúcia"){
            if(isset($update["address-city"]) && $update["address-city"]){
                $id = $update["kraj"]."-".$update["okres"]."-".$update["address-city"];
            }else{
                $id = $update["kraj"]."-".$update["okres"]."-".$update["city"];
            }
            $id = \AsyncWeb\Text\Texts::clear($id);
            $update["id3"] = $id;
            DB::u("data_obce_inst_core",md5($id),$update);
            return;
        }
        if($update["page"] == "Informácie o obci"){
            // .. todo
        }
        
    }
}

echo "\nDONE ".date("c")."\n";
Cron::end();