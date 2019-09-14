<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
use AsyncWeb\DataMining\WebMining;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$work = true;
echo "\ncounting rows..";

$i=0;

$webstable = "data_mze_ezp_webs";
  
$res = DB::qb($webstable,[
    "cols"=>["id","id2","data","web"],
    ]);
$cc = DB::num_rows($res);


$pravidla = array(
	"table"=>"data_mze_ezp_core",
	"id"=>"id3",
	"from_encoding"=>"utf-8",
	"cols"=>array(
		"ico"=>array(
            "xpath"=>"//dt[contains(.,'IČO')]/following-sibling::dd",
			"filter"=>array("trim"=>true,),
		),
		"name"=>array(
            "xpath"=>"//dt[contains(.,'Obchodní firma')]/following-sibling::dd",
			"filter"=>array("trim"=>true,),
		),
		"surname"=>array(
            "xpath"=>"//dt[contains(.,'Jméno')]/following-sibling::dd",
			"filter"=>array("trim"=>true,),
		),
		"lastname"=>array(
            "xpath"=>"//dt[contains(.,'Příjmení')]/following-sibling::dd",
			"filter"=>array("trim"=>true,),
		),
		"entrydate"=>array(
            "xpath"=>"//dt[contains(.,'Datum zápisu')]/following-sibling::dd",
			"filter"=>array("trim"=>true,),
		),
		"seat-line-1"=>array(
            "xpath"=>"//h2[contains(.,'Sídlo')]/following-sibling::dl/dd[1]",
			"filter"=>array("trim"=>true,),
		),
		"seat-line-2"=>array(
            "xpath"=>"//h2[contains(.,'Sídlo')]/following-sibling::dl/dd[2]",
			"filter"=>array("trim"=>true,),
		),
		"seat-line-3"=>array(
            "xpath"=>"//h2[contains(.,'Sídlo')]/following-sibling::dl/dd[3]",
			"filter"=>array("trim"=>true,),
		),
		"seat-line-4"=>array(
            "xpath"=>"//h2[contains(.,'Sídlo')]/following-sibling::dl/dd[4]",
			"filter"=>array("trim"=>true,),
		),
		"source"=>array(
			"value"=>"",
		),
	),
	);

DB::query("drop table ".$pravidla["table"]);

while($row=DB::f($res)){
    
    $i++;
    if($i%10==1) echo ".";
	if($i%1000==1) echo "\n$i/$cc/".date("c")."";
    
    
	$data = gzuncompress($row["data"]);
    $data = str_replace('</title>','</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />',$data);
    //file_put_contents("dev01.html",$data);
    $last = substr(strrchr($row["web"], "/"), 1);

    $pravidla["cols"]["id3"] = ["value"=>$last];

    $pravidla["cols"]["source"]["value"] = $row["web"];
    
	$wm = new WebMining();
    $ret = $wm->spracuj($pravidla,trim($data),$row["od"]);
//var_dump($ret);
//    exit;
}

Cron::end();
