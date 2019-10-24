<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c")."\n";

$spracFrom = "0";
$spracTo = "7";
$spracFail = "12";


$processor = new Processor("core");

require_once("mapping.php");

$balancer = new \AsyncWeb\System\CPU\LoadBalancer(0.2,5000,0.3,50000,0.5,2000000);
$webstable = "data_opendata_data_webs";

//"cols"=>["context","datova-sada"],//"id2"=>"7d9e76980a2f7d41240d20e2c7bbf777",
/*
$testd = DB::gr("data_opendata_distribuce_core",["odkazkestazeni"=>"https://opendata.mzcr.cz/data/uzis/faktury/2019/02/faktury.csv"]);
var_dump($testd);
$tests = DB::gr("data_opendata_sady_core",["datova-sada"=>$testd["datova-sada"]]);
var_dump($tests);
exit;/**/

\AsyncWeb\DB\MysqliServer::$DEFAULT_DATA_TYPE = "text";


$res = DB::qb("data_opendata_sady_core",["where"=>[["col"=>"context","op"=>"isnot","value"=>null]]]);//"id2"=>"7d9e76980a2f7d41240d20e2c7bbf777",
$cc = DB::num_rows($res);$i = 0;
while($sada=DB::f($res)){

    $i++;
    if($i%1==0) echo ".";
	if($i%100==0) echo "$i/".$sada["id"]."/$cc/".date("c")."\n";
    $balancer->wait();

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
    if(!$process){
        //DB::u($webstable,$row["id2"],["spracovany"=>$spracFail],false,false,false);
        continue;
    }
    
    $res2 = DB::qb("data_opendata_distribuce_core",["where"=>["datova-sada"=>$sada["datova-sada"]]]);//"cols"=>["odkazkestazeni"],
    


    $D = [];
    $removepolozky = false;
    $download = [];
    
    while($distribuce=DB::f($res2)){
        $balancer->wait();
        
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
    
    $containsPolozky = null;
    $removepolozky = false;
    foreach($download[$link] as $distribuce){
        $name = Texts::clear($distribuce["nazev"]);
        $name2 = Texts::clear($distribuce["odkazkestazeni"]);
        $result = (strpos($name,"polozk") !== false || strpos($name2,"polozk") !== false);
        
        //var_dump("$name:$name2:$result");
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
            $name2 = Texts::clear($distribuce["odkazkestazeni"]);
            $result = (strpos($name,"polozk") !== false || strpos($name2,"polozk") !== false);
            if($result){
                unset($download[$link][$k]);
            }
        }
    }
    
    foreach($download[$link] as $k=>$distribuce){
        $balancer->wait();
        $path = $distribuce["odkazkestazeni"];
        if(!$path) continue;
        $last = Page::getLastTime($path,"data_opendata_data_webs");
        $web = DB::qbr($webstable,["cols"=>"spracovany","where"=>["id2"=>md5($path)]]);
        if(!$web) continue;// web was not yet downloaded
        
        if($web["spracovany"] != $spracFrom){
            DB::u($webstable,$row["id2"],["spracovany"=>$spracFail],false,false,false);
            continue; // skip if != $spracFrom
        }/**/
        
        $row = DB::qbr($webstable,["where"=>["id2"=>md5($path)]]);
        
        if(substr($row["data"],0,7) == "file://"){
            $data = file_get_contents(substr($row["data"],7));
        }else{
            $data = gzuncompress($row["data"]);
        }
        if(substr($data,0,5) == "HTTP/"){
            \AsyncWeb\Connectors\MyCurl::divideHeaders($data,$headers,true);
        }

        switch($link){
            case "csv":
                 
                if(count(explode("\n",$data)) > 10){
                    //echo "content: ".strlen($data)."\n";
                }
                if($distribuce["charset"]){
                    $data = iconv($distribuce["charset"],"UTF-8",$data);
                }

                $R = [];
                if(isset($rename[$sada["context"]])){
                    $R = $rename[$sada["context"]];
                }
                $H = [];
                if(isset($handlers[$sada["context"]])){
                    $H = $handlers[$sada["context"]];
                }

                $C = [];
                if(isset($config[$sada["context"]])){
                    $C = $config[$sada["context"]];
                }
                $processor->processCSV($data,$row,$sada,$C,$R,$H);
                DB::u($webstable,$row["id2"],["spracovany"=>$spracTo],false,false,false);
            break;        
            case "zip": 
            case "csvzip":
                $data = gzuncompress($row["data"]);

                if($distribuce["charset"]){
                    $data = iconv($distribuce["charset"],"UTF-8",$data);
                }
                
                $R = [];
                if(isset($rename[$sada["context"]])){
                    $R = $rename[$sada["context"]];
                }
                $H = [];
                if(isset($handlers[$sada["context"]])){
                    $H = $handlers[$sada["context"]];
                }
                $C = [];
                if(isset($config[$sada["context"]])){
                    $C = $config[$sada["context"]];
                }
                $processor->processZIPCSV($data,$row,$sada,$C,$R,$H);
                
                DB::u($webstable,$row["id2"],["spracovany"=>$spracTo],false,false,false);
            break;
            case "csvgz":
            case "gz":


                if($distribuce["charset"]){
                    $data = iconv($distribuce["charset"],"UTF-8",$data);
                }
                
                $R = [];
                if(isset($rename[$sada["context"]])){
                    $R = $rename[$sada["context"]];
                }
                $H = [];
                if(isset($handlers[$sada["context"]])){
                    $H = $handlers[$sada["context"]];
                }
                $C = [];
                if(isset($config[$sada["context"]])){
                    $C = $config[$sada["context"]];
                }
                $processor->processGZCSV($data,$row,$sada,$C,$R,$H);
                
                DB::u($webstable,$row["id2"],["spracovany"=>$spracTo],false,false,false);
            break;
            case "xml":
                // not processing atm
                DB::u($webstable,$row["id2"],["spracovany"=>$spracFail],false,false,false);
            break;
            default:

                var_dump("nepoznam data");
                //var_dump($sada);
                //var_dump($distribuce);
                var_dump($link);
                Cron::end();
                exit;            
            break;
        }
    }
    
}



class Processor{
    public static $skipExtensions = ["prj","shp","shx","dbf","vfk","vkm","gml","xsd","cpg","sbn","sbx","dxf","dgn","pgw","png","kml","geojson","gfs","n3","dwg","pdf","sav","xlsx","pptx","avl","qpj","html","qix",];

    public $LARGE_FILE_DIR = "/mnt/2tb/cr/opendata/";
    public function makeLargeFileName($path){
        return $this->LARGE_FILE_DIR.md5($path).substr($path,-10);
    }
    private $level = "core";
    private $balancer = null;
    /**
    * level one|core One for all
    */
    public function __construct($level = "1"){
        $this->level = $level;
        $this->balancer = new \AsyncWeb\System\CPU\LoadBalancer(0.2,5000,0.3,50000,0.5,2000000);
    }
    public function processCSV($data,$row,$sada,$config,$renameColumns,$handlers){
        $this->balancer->wait();
        //var_dump("data_all_".$this->level."_".str_replace("-","_",$sada["context"]));
        $ret = \AsyncWeb\Text\CSV2DB::process($data,"devczfast.data_all_".$this->level."_".str_replace("-","_",$sada["context"]),$config,["source"=>$row["web"]],$renameColumns,$handlers);
        //var_dump($ret);
    }
    public function exctractGZ($file_name){

        $buffer_size = 4096; // read 4kb at a time
        $out_file_name = str_replace('.gz', '', $file_name); 

        // Open our files (in binary mode)
        $file = gzopen($file_name, 'rb');
        $out_file = fopen($out_file_name, 'wb'); 

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // Files are done, close files
        fclose($out_file);
        gzclose($file);
        return $out_file_name;
    }
    public function processGZCSV($data,$row,$sada,$config,$renameColumns,$handlers){
        $zippath = "/cron2/apps/cr-opendata/data.gz";
        file_put_contents($zippath,$data);
        $file = $this->exctractGZ($zippath);
        $content = file_get_contents($file);
        $this->processCSV($content,$row,$sada,$config,$renameColumns,$handlers);
    }
    
    public function processZIPCSV($file,$row,$sada,$config,$renameColumns,$handlers,$tmpname="data.zip"){


        $zip = new ZipArchive();
        $zippath = "/cron2/apps/cr-opendata/$tmpname";
        file_put_contents($zippath,$file);
        $done = false;
        if ($zip->open($zippath) === true) {
            
            for($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);
                if($fileinfo["extension"] == ""){
                    continue;
                }
                
                if($done){
                    echo "Subor $path obsahuje viac suborov!!\n";
                    break;
                }
                if(strtolower($fileinfo["extension"]) == "csv" || strtolower($fileinfo["extension"]) == "txt"){
                    $done = true;
                    copy("zip://".$zippath."#".$filename, "/cron2/apps/cr-opendata/outs07.csv");
                }else if(strtolower($fileinfo["extension"]) == "xml"){
                    continue;
                }else if(strtolower($fileinfo["extension"]) == "zip"){
                    copy("zip://".$zippath."#".$filename, "/cron2/apps/cr-opendata/outs07.zip");
                    
                    $this->processZIPCSV(file_get_contents("/cron2/apps/cr-opendata/outs07.zip"),$row,$sada,$config,$renameColumns,$handlers,$tmpname="data-lvl2.zip");
                    
                }else{
                    if(in_array(strtolower($fileinfo["extension"]),self::$skipExtensions)){
                        continue;
                    }
                    echo(strtolower($fileinfo["extension"])." extension is not supported");
                }
            }
            $zip->close();                  
        }
        if($done){
            $data = file_get_contents("/cron2/apps/cr-opendata/outs07.csv");
            echo "$i ".$sada["context"]." ".$row["web"]."\n";
            $this->balancer->wait();
        
            AsyncWeb\Text\CSV2DB::process($data,"devczfast.data_all_".$this->level."_".str_replace("-","_",$sada["context"]),$config,["source"=>$row["web"]],$renameColumns,$handlers);
        }
        
    }
}

Cron::end();
echo "done ".date("c")."\n";