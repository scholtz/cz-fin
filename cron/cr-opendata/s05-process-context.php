<?php
echo "deprecated .. use s07";exit;

use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;


require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c")."\n";


$spracFrom = "3";
$spracTo = "3";
$spracFail = "9";

$web2sada = [];


$webstable = "data_opendata_data_webs";

$basicwhere = [];//["spracovany"=>$spracFrom];
$where = $basicwhere;
$work = true;
echo "\ncounting rows..";
//$row = DB::qbr($webstable,["cols"=>["c"=>"count(`id`)"],"where"=>$where]);
//$cc = $row["c"];
$cc = 70000;

echo "idem spracovat $cc riadkov\n";
$i=0;


$config = [];
$processor = new Processor();
require_once("mapping.php");

while($work){  

  
$res = DB::qb($webstable,[
    "limit"=>100,
    "cols"=>["id","id2","web","data"],
    "where"=>$where,
    "order"=>["id"=>"asc"],
    ]);
$c = DB::num_rows($res);
if(!$c) $work = false;

while($row=DB::f($res)){
    
    $i++;
    if($i%1==0) echo ".";
	if($i%100==0) echo "$i/".$row["id"]."/$cc/".date("c")."\n";
    
    
    $where = $basicwhere;
    $where[] = ["col"=>"id","op"=>"gt","value"=>$row["id"]];
    
    $distribuce = DB::gr("data_opendata_distribuce_core",["link_hash"=>md5($row["web"])]);
    $sada = DB::gr("data_opendata_sady_core",md5($distribuce["datova-sada"]));
    $process = true;
    if(!$sada["context"]) {
        $process = false;
    }
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
        DB::u($webstable,$row["id2"],["spracovany"=>$spracFail],false,false,false);
        continue;
    }
    
    
    switch($sada["context"]){
        case "puda": 
            $process = false;
            break;
    }
    if(!$process){
        DB::u($webstable,$row["id2"],["spracovany"=>$spracTo],false,false,false);
        continue;
    }
    
    
    
    if($sada[$l = "csvgz"]){
        $link = $l;
    }else if($sada[$l = "csvzip"]){
        $link = $l;
    }else if($sada[$l = "csvtgz"]){
        $link = $l;
    }else if($sada[$l = "csv"]){
        $link = $l;
    }else if($sada[$l = "xmlgz"]){
        $link = $l;
    }else if($sada[$l = "xmlzip"]){
        $link = $l;
    }else if($sada[$l = "xmltgz"]){
        $link = $l;
    }else if($sada[$l = "xml"]){
        $link = $l;
    }else if($sada[$l = "gz"]){
        $link = $l;
    }else if($sada[$l = "zip"]){
        $link = $l;
    }else if($sada[$l = "tgz"]){
        $link = $l;
    }
    
    
    $path = $sada[$link];
    var_dump($sada);
        var_dump($path);exit;
    
    if($path != $row["web"]){
        // we are going to process this csv file from plain format not from the gzip
        DB::u($webstable,$row["id2"],["spracovany"=>$spracFail],false,false,false);
    }else{
        switch($link){
            case "csv":
                 
                if(count(explode("\n",$data)) > 10){
                    echo "content: ".strlen($data)."\n";
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

                $data = gzuncompress($row["data"]);
                $processor->processCSV($data,$row,$sada,$config,$R,$H);
                
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
                $data = gzuncompress($row["data"]);
                $processor->processZIPCSV($data,$row,$sada,$C,$R,$H);
                
                DB::u($webstable,$row["id2"],["spracovany"=>$spracTo],false,false,false);
            break;
            case "csvgz":
            case "gz":

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
                $processor->processGZCSV($data,$row,$sada,$C,$R,$H);
                
                DB::u($webstable,$row["id2"],["spracovany"=>$spracTo],false,false,false);
            break;
            default:

                var_dump("nepoznam data");
                var_dump($sada);
                var_dump($distribuce);
                Cron::end();
                exit;            
            break;
        }
    }
    
}
}

class Processor{
    private $level = "core";
    private $balancer = null;
    /**
    * level one|core One for all
    */
    public function __construct($level = "one"){
        $this->level = $level;
        $this->balancer = new \AsyncWeb\System\CPU\LoadBalancer(0.2,5000,0.3,50000,0.5,2000000);
    }
    public function processCSV($data,$row,$sada,$config,$renameColumns,$handlers){
        $this->balancer->wait();
        var_dump("data_all_".$this->level."_".str_replace("-","_",$sada["context"]));
        $ret = \AsyncWeb\Text\CSV2DB::process($data,"data_all_".$this->level."_".str_replace("-","_",$sada["context"]),$config,["source"=>$row["web"]],$renameColumns,$handlers);
        var_dump($ret);
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
        $zippath = "data.gz";
        file_put_contents($zippath,$data);
        $file = $this->exctractGZ($zippath);
        $content = file_get_contents($file);
        $this->processCSV($content,$row,$sada,$config,$renameColumns,$handlers);
    }
    
    public function processZIPCSV($file,$row,$sada,$config,$renameColumns,$handlers){


        $zip = new ZipArchive();
        $zippath = "data.zip";
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
                if(strtolower($fileinfo["extension"]) == "csv"){
                    $done = true;
                    copy("zip://".$zippath."#".$filename, "outs05.csv");
                }else{
                    echo(strtolower($fileinfo["extension"])." extension is not supported");
                }
            }
            $zip->close();                  
        }
        if($done){
            $data = file_get_contents("outs05.csv");
            echo "$i ".$sada["context"]." ".$row["web"]."\n";
            $this->balancer->wait();
        
            AsyncWeb\Text\CSV2DB::process($data,"data_all_".$this->level."_".str_replace("-","_",$sada["context"]),$config,["source"=>$row["web"]],$renameColumns,$handlers);
        }
        
    }
}
Cron::end();
echo "done ".date("c")."\n";