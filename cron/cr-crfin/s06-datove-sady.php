<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$statstable = "data_czfin_stats";
$force =true;


 $process = [
    "data_all_core_objednavky"=>[
        "context"=>"objednavky",    
        "name"=>"Objednávky",
        "val-col"=>"data-celkova-castka",
    ],
    "data_all_core_faktury"=>[
        "context"=>"faktury",    
        "name"=>"Faktúry",
        "val-col"=>"data-castka-s-dph",
    ],
    "data_all_core_pokuty"=>[
        "context"=>"pokuty",    
        "name"=>"Pokuty",
    ],
    "data_all_core_rozhodnuti"=>[
        "context"=>"rozhodnuti",
        "name"=>"Rozhodnutí",
    ],
    "data_all_core_prostory"=>[
        "context"=>"rozhodnuti",    
        "name"=>"Prostory",
    ],
    "data_all_core_setreni"=>[
        "context"=>"setreni",    
        "name"=>"Šetrení",
    ],
    "data_all_core_seznamy_podnikatelov"=>[
        "context"=>"seznamy",    
        "name"=>"Seznamy podnikatelov",
    ],
    "data_all_core_seznam_vladnich_instituci"=>[
        "context"=>"instituce",    
        "name"=>"Státne instituce",
    ],
    "data_mze_dpb_core"=>[
        "context"=>"mze_vymery",    
        "name"=>"Výmery poľnohospodárskych oblastí",
        "ico-col"=>"ico",
        "date-col"=>"platnostod",
        "val-col"=>"vymera",
    ],
    ];

$data = [];
//$data["ČESKÁ NÁRODNÍ BANKA"]["Seznam osob oprávněných provozovat směnárenskou činnost na území České republiky"] = true;
$data["Ministerstvo financí"]["ARES Full Snapshot"]=true;
$data["Ministerstvo zemědělství"]["Veřejný export dat LPIS"] = true;
$data["Český statistický úřad"]["Registr ekonomických subjektů"]=true;

foreach($process  as $T=>$conf){
    $res = DB::qb($T,["distinct"=>true,"cols"=>["source"]]);
    while($row=DB::f($res)){
        $distribuce = DB::gr("data_opendata_distribuce_core",["link_hash"=>md5($row["source"])]);
        if(!$distribuce){
            $distribuce = DB::gr("data_opendata_distribuce_core",["odkazkestazeni"=>$row["source"]]);
        }

        $sada = DB::gr("data_opendata_sady_core",md5($distribuce["datova-sada"]));
        if(!$sada){
            var_dump($sada);
            var_dump($distribuce);
            var_dump($row);
            exit;
        }
        $data[$sada["poskytovatel"]][$sada["nazev"]] = true;
    }
}

$ret = '<h1>Seznam použitých dátových zdroju</h1>
<div class="row">';
foreach($data as $k=>$v){
    $ret.='<br><div class="col-md-3"><div class="card"><h2 class="card-header">'.$k.'</h2><div class="card-body">';
    $ret.= '<ul>';
    foreach($v as $k2=>$t){
        $ret.='<li>'.$k2.'</li>';
    }
    $ret.= '</ul></div></div></div>';
}
$ret.='</div>';
$maintable = "data_czfin_pages";
DB::u($maintable,"datove-sady",["page"=>$ret]);

$r = DB::g($table = $maintable);
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
