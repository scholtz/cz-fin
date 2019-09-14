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


if($force || !DB::gr($statstable,"ARES")){

    $allCompanies = DB::qbr("data_arescz_company_core",["cols"=>["c"=>"count(id2)"]]);
    $allVymaz = DB::qbr("data_arescz_company_core",["cols"=>["c"=>"count(id2)"],"where"=>[["col"=>"datumvymazu","op"=>"isnot","value"=>null]]]);
    $allActiveVymaz = DB::qbr("data_arescz_company_core",["cols"=>["c"=>"count(id2)"],
    "where"=>
    [["col"=>"clear","op"=>"like","value"=>"%-v-likvi%"],
    ["col"=>"datumvymazu","op"=>"isnot","value"=>null]]]);

    DB::u($statstable,"ARES",["name"=>"ARES","all"=>$allCompanies["c"],"deleted"=>$allVymaz["c"],"akt-v-likv"=>$allActiveVymaz["c"]]);
    
}

if($force || !DB::gr($statstable,md5("RES"))){

    $allRES = DB::qbr("data_firmy_ares02_core",["cols"=>["c"=>"count(id2)"]]);
    $allIco2Nace = DB::qbr("data_firmy_ares02_list_core",["cols"=>["c"=>"count(id2)"]]);
    
    DB::u($statstable,"RES",["name"=>"RES","all"=>$allRES["c"],"ico-comb"=>$allIco2Nace["c"]]);
    
}

foreach($process  as $T=>$conf){
    
    if($force || !DB::gr($statstable,$T)){
        
        $all = DB::qbr($T,["cols"=>["c"=>"count(id2)"]]);
        $icocol = "data-ico-clear";
        if(isset($conf["ico-col"])) $icocol = $conf["ico-col"];
        $allDistinctIco = DB::qbr($T,["distinct"=>true,"cols"=>["c"=>"count($icocol)"]]);
        $valueSum = ["c"=>""];
        if(isset($conf["val-col"])){
            $valcol = $conf["val-col"];
            $valueSum = DB::qbr($T,["cols"=>["c"=>"sum(`$valcol`)"]]);
        }
        DB::u($statstable,md5($T),["name"=>$conf["name"],"all"=>$all["c"],"distinct-ico"=>$allDistinctIco["c"],"distinct-ico"=>$allDistinctIco["c"],"valuesum"=>$valueSum["c"],"table"=>$T]);
    }
}


$r = DB::g($table = $statstable);
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
