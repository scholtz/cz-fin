<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c")."\n";



$processor = new OpenDataProcessor();

class OpenDataProcessor{
    public $LARGE_FILE_DIR = "/mnt/2tb/cr/opendata/";
    public function makeLargeFileName($path){
        return $this->LARGE_FILE_DIR.md5($path).substr($path,-10);
    }
}

$balancer = new \AsyncWeb\System\CPU\LoadBalancer(0.2,5000,0.3,50000,0.5,2000000);

$res = DB::qb("data_opendata_sady_core",["where"=>[["col"=>"context","op"=>"isnot","value"=>null]]]);//"cols"=>["context","datova-sada"],//"id2"=>"7d9e76980a2f7d41240d20e2c7bbf777",
$cc = DB::num_rows($res);$i = 0;

while($sada=DB::f($res)){

    $i++;
    if($i%1==0) echo ".";
	if($i%100==0) echo "$i/".$sada["id"]."/$cc/".date("c")."\n";

    $process = true;
    switch($sada["context"]){
        case "adresy": 
        case "volebni-okrsky": 
        case "odpad": 
        case "ciselniky": 
        case "jizdni-rady": 
        case "parcely": 
            $process = false;
            break;
    }
    if(!$process) continue;
    
    $balancer->wait();
    $res2 = DB::qb("data_opendata_distribuce_core",["where"=>["datova-sada"=>$sada["datova-sada"]]]);//"cols"=>["odkazkestazeni"],
    


    $D = [];
    $removepolozky = false;
    $download = [];
    
    while($distribuce=DB::f($res2)){
        
        $format = Texts::clear($distribuce["format"]);
        if(strtolower(substr($distribuce["odkazkestazeni"],-7)) == ".csv.gz") $format = "csvgz";
        if(strtolower(substr($distribuce["odkazkestazeni"],-7)) == ".xml.gz") $format = "xmlgz";
        if(strtolower(substr($distribuce["odkazkestazeni"],-8)) == ".csv.tgz") $format = "csvtgz";
        if(strtolower(substr($distribuce["odkazkestazeni"],-8)) == ".xml.tgz") $format = "xmltgz";
        if(strtolower(substr($distribuce["odkazkestazeni"],-8)) == ".csv.zip") $format = "csvzip";
        if(strtolower(substr($distribuce["odkazkestazeni"],-8)) == ".xml.zip") $format = "xmlzip";
        if(strtolower(substr($distribuce["odkazkestazeni"],-5)) == ".xlsx") $format = "xlsx";
        if(strtolower(substr($distribuce["odkazkestazeni"],-5)) == ".xls") $format = "xls";
        
        
        if(!$format && strtolower(substr($distribuce["odkazkestazeni"],-4)) == ".xml") $format = "xml";
        if(!$format && strtolower(substr($distribuce["odkazkestazeni"],-4)) == ".csv") $format = "csv";
        if(!$format && strtolower(substr($distribuce["odkazkestazeni"],-4)) == ".zip") $format = "zip";
        if(!$format && strtolower(substr($distribuce["odkazkestazeni"],-3)) == ".gz") $format = "gz";
        if(!$format && strtolower(substr($distribuce["odkazkestazeni"],-4)) == ".tgz") $format = "tgz";

        if(!isset($download[$format])){
            $download[$format] = [];
        }
        
        $download[$format][] = $distribuce;

    }
    
    
    
    $link = false;
    if(isset($download[$l = "csvgz"])){
        $link = $l;
    }else if(isset($download[$l = "csvzip"])){
        $link = $l;
    }else if(isset($download[$l = "csvtgz"])){
        $link = $l;
    }else if(isset($download[$l = "csv"])){
        $link = $l;
    }else if(isset($download[$l = "xmlgz"])){
        $link = $l;
    }else if(isset($download[$l = "xmlzip"])){
        $link = $l;
    }else if(isset($download[$l = "xmltgz"])){
        $link = $l;
    }else if(isset($download[$l = "xml"])){
        $link = $l;
    }else if(isset($download[$l = "gz"])){
        $link = $l;
    }else if(isset($download[$l = "zip"])){
        $link = $l;
    }else if(isset($download[$l = "tgz"])){
        $link = $l;
    }
    if(!$link) continue;
    
    $containsPolozky = null;
    $removepolozky = false;
    foreach($download[$link] as $distribuce){
        $name = Texts::clear($distribuce["nazev"]);
        $result = (strpos($name,"polozk") !== false);
        
        if($result){
            if($containsPolozky === null){
                $containsPolozky = $result;
            }else{
                if($containsPolozky !== $result){
                    $removepolozky = true;
                    break;
                }
            }
        }else{
            if($containsPolozky === null){
                $containsPolozky = $result;
            }else{
                if($containsPolozky !== $result){
                    $removepolozky = true;
                    break;
                }
            }
        }
    }
    if($removepolozky){
        foreach($download[$link] as $k=>$distribuce){
            $name = Texts::clear($distribuce["nazev"]);
            $result = (strpos($name,"polozk") !== false);
            if($result){
                unset($download[$link][$k]);
            }
        }
    }
    
    foreach($download[$link] as $k=>$distribuce){
        $path = $distribuce["odkazkestazeni"];
        if(!$path) continue;
        $last = Page::getLastTime($path,"data_opendata_data_webs");
        
        if(!$last){
            echo $path." !!!NEW!!!\n";
        }else{
            //echo $path." ".date("c",$last)."\n";
        }
        if($last < time() - 3600 * rand(30,200)){
            Page::downloadWithEtag($path,"data_opendata_data_webs",$processor->makeLargeFileName($path),true,["spracovany"=>"0"]);
        }
    }
    
}


Cron::end();
echo "done ".date("c")."\n";