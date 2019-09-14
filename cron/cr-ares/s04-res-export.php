<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");


$data = explode("\n",file_get_contents("ICO-list.csv"));
$basic = DB::gr("data_firmy_ares02_core");
$out = '';
foreach($basic as $k=>$v){
$out .= '"'.str_replace('"','\\"',$k).'",';
}
$out .="\n";
foreach($data as $ico){
    $basic = DB::gr("data_firmy_ares02_core",["id2"=>trim($ico)]);
    if(!$basic){
        continue;
    }
    foreach($basic as $k=>$v){
        $out .= '"'.str_replace('"','\\"',$v).'",';
    }
    $out .="\n";
}
file_put_contents("export.csv",$out);
Cron::end();
