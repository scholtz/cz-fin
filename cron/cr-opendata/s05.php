<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");


$spracFrom = "3";
$spracTo = "3";

$web2sada = [];


$webstable = "data_opendata_data_webs";

$basicwhere = ["spracovany"=>$spracFrom];
$where = $basicwhere;
$work = true;
echo "\ncounting rows..";
$cc = 3279668;
$row = DB::qbr($webstable,["cols"=>["c"=>"count(`id`)"],"where"=>$where]);
$cc = $row["c"];
echo "idem spracovat $cc riadkov\n";
$i=0;


$config = [];
$processor = new Processor();
require_once("mapping.php");

while($work){  

  
$res = DB::qb($webstable,[
    "limit"=>10000,
    "cols"=>["id","id2","web","data"],
    "where"=>$where,
    "order"=>["id"=>"asc"],
    ]);
$c = DB::num_rows($res);
if(!$c) $work = false;

while($row=DB::f($res)){
    
    $i++;
    if($i%1==0) echo ".";
	if($i%100==0) echo "\n$i/$cc/".date("c")."";
    
    $where = $basicwhere;
    $where[] = ["col"=>"id","op"=>"gt","value"=>$row["id"]];
    
    $distribuce = DB::gr("data_opendata_distribuce_core",["link_hash"=>md5($row["web"])]);
    $sada = DB::gr("data_opendata_sady_core",md5($distribuce["datova-sada"]));
    if(!$sada["context"]) continue;
    if($sada["context"] == "adresy") continue;
    if($sada["context"] == "volebni-okrsky") continue;
    
    if($sada["context"] != "seznam-vladnich-instituci") continue;
    
    if($sada["plaincsv"] == $row["web"]){
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
        $processor->processCSV($data,$row,$sada,$config,$R,$H);
        
        DB::u($webstable,$row["id2"],["spracovany"=>$spracTo],false,false,false);
        
    }else if (!$sada["plaincsv"] && $sada["zipcsv"] ){

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
    }else if($sada["plaincsv"]){
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
        $processor->processCSV($data,$row,$sada,$C,$R,$H);
        
        DB::u($webstable,$row["id2"],["spracovany"=>$spracTo],false,false,false);
        
    }else{
        var_dump("nepoznam data");
        var_dump($sada);
        var_dump($distribuce);
        Cron::end();
        exit;
    }
    
    
    
    
}
}

class Processor{
    public function processCSV($data,$row,$sada,$config,$renameColumns,$handlers){
        echo "$i ".$sada["context"]." ".$row["web"]."\n";
        AsyncWeb\Text\CSV2DB::process($data,"data_all_core_".str_replace("-","_",$sada["context"]),$config,["source"=>$row["web"]],$renameColumns,$handlers);
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
                $done = true;
                copy("zip://".$zippath."#".$filename, "outs05.csv");
                
                
            }
            $zip->close();                  
        }
        if($done){
            $data = file_get_contents("outs05.csv");
            AsyncWeb\Text\CSV2DB::process($data,"data_all_core_".str_replace("-","_",$sada["context"]),$config,["source"=>$row["web"]],$renameColumns,$handlers);
        }
        
    }
}
Cron::end();
