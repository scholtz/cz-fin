<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$ratingtable = "data_czfin_rating";

$res = DB::qb($ratingtable,["cols"=>["id2"],"order"=>["body"=>"desc"]]);
$count = DB::num_rows($res);
$current = $count;
$i = 0;
while($row=DB::f($res)){
    
    $i++;
    if($i%10==1) echo ".";
    if($i%1000==1) echo "\n$i/$count/".date("c")."";
    DB::u($ratingtable,$row["id2"],["rating"=>$current--,"ratingmax"=>$count],false,false,false);
}



$r = DB::g($table = $ratingtable);
if(DB::num_rows($r) > 0){
    
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

Cron::end();
