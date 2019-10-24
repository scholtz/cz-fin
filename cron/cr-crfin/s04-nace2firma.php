<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$nace2firmatable = "data_czfin_nace2firma";
$ratingtable = "data_czfin_rating";
$force =false;

$firma2nace = [];
echo "\nidem fetchnut data_firmy_ares02_list_core";
$res = DB::qb("data_firmy_ares02_list_core",["cols"=>["ico","nace"]]);
$i = 0;
$cc = DB::num_rows($res);

while($row=DB::f($res)){
    $i++;
    if($i%100==0) echo ".";
	if($i%10000==0) echo "\n$i/$cc/".date("c")."";
    if(strlen($row["nace"])==5){
        $row["nace"] = substr($row["nace"],0,4);
    }
    $firma2nace[$row["ico"]][$row["nace"]] = true;
    
    $nacedb = DB::gr("sknace",["id4"=>$row["nace"]]);
    
    while($nacedb["id4"]){
        $firma2nace[$row["ico"]][$nacedb["id4"]] = true;
        $nacedb = DB::gr("sknace",["id4"=>$nacedb["parent"]]);
    }
}
$nace2firma = [];
$counter = [];
$i = 0;

$ratingram = [];
echo "\nidem fetchnut $ratingtable";
$res = DB::qb($ratingtable);
$i = 0;
$cc = DB::num_rows($res);
while($row=DB::f($res)){
    $i++;
    if($i%100==0) echo ".";
	if($i%10000==0) echo "\n$i/$cc/".date("c")."";
    $ratingram[$row["id2"]] = $row;
}

$i = 0;
echo "\nidem spracovat nace2firma";
$res = DB::qb($ratingtable,["cols"=>["id2"],"order"=>["rating"=>"desc"]]);
$cc = DB::num_rows($res);
while($row=DB::f($res)){
    if(isset($firma2nace[$row["id2"]]))
    foreach($firma2nace[$row["id2"]] as $nace=>$t){
        
        $i++;
        if($i%100==0) echo ".";
        if($i%10000==0) echo "\n$i/$cc/".date("c")."";
        $counter[$nace]++;
        
        if(!isset($nace2firma[$nace])) $nace2firma[$nace] = [];
        if(count($nace2firma[$nace]) >= 50) continue;
        $nace2firma[$nace][] = $row;
    }
}

$config["cols"][$colname="data"]["type"] = "text";
$config["cols"][$colname="counter"]["type"] = "int";

foreach($nace2firma as $nace=>$data){
    
    foreach($data as $k=>$v){
        if(isset($ratingram[$v["id2"]])){
            $data[$k] = $ratingram[$v["id2"]];
        }
    }
    
    DB::u($nace2firmatable,$nace,["data"=>base64_encode(json_encode($data)),"counter"=>$counter[$nace]],$config);
    $config = false;
}





$r = DB::g($table = $nace2firmatable);
if(DB::num_rows($r) > 0){
    
    $rand = rand(10000,99999);
    echo "\ncopying to prod tmp table schema `out`.`${table}_tmp$rand`\n";
    DB::query("CREATE TABLE `out`.`${table}_tmp$rand` LIKE `devcz`.`$table`");
    echo DB::error();
    echo "\ncopying to prod tmp table data\n";
    DB::query("INSERT INTO `out`.`${table}_tmp$rand` SELECT * FROM `devcz`.`$table` where do = 0");
    echo DB::error();
    echo "\ndropping table\n";
    DB::query("drop table if exists `out`.`$table`");
    echo DB::error();
    echo "\nrenaming table `${table}_tmp$rand` TO `out`.`$table`\n";
    DB::query("RENAME TABLE `out`.`${table}_tmp$rand` TO `out`.`$table`");
    echo DB::error();
    echo "DONE";	
}

Cron::end();
