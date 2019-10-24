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

DB::query("truncate table $statstable");

require_once("/ocz/vhosts/cz-fin.com/prod01/src/AT/src/Classes/PageBuilder.php");
$pagebuilder = new \AT\Classes\PageBuilder();
$process = $pagebuilder->makeConfig();

if($force || !DB::gr($statstable,"4c522b71d948e7154f2d21d0561330bc")){
    echo "ARES\n";
    $allCompanies = DB::qbr("data_arescz_company_core",["cols"=>["c"=>"count(id2)"]]);
    $allVymaz = DB::qbr("data_arescz_company_core",["cols"=>["c"=>"count(id2)"],"where"=>[["col"=>"datumvymazu","op"=>"isnot","value"=>null]]]);
    $allActiveVymaz = DB::qbr("data_arescz_company_core",["cols"=>["c"=>"count(id2)"],
    "where"=>
    [["col"=>"clear","op"=>"like","value"=>"%-v-likvi%"],
    ["col"=>"datumvymazu","op"=>"isnot","value"=>null]]]);

    $last = DB::qbr("data_arescz_company_core",["cols"=>["c"=>"max(od)"]]);

    DB::u($statstable,"4c522b71d948e7154f2d21d0561330bc",["name"=>"ARES","all"=>$allCompanies["c"],"deleted"=>$allVymaz["c"],"akt-v-likv"=>$allActiveVymaz["c"],"last"=>$last["c"]]);
    
}

if($force || !DB::gr($statstable,"625408c1d25ac7c595aa008dcffc252d")){
    echo "MFCRBadDic\n";
    $allCompanies = DB::qbr("data_mfcr_dic_bad_core",["cols"=>["c"=>"count(id2)"]]);
    $allVymaz = DB::qbr("data_mfcr_dic_bad_core",["cols"=>["c"=>"count(id2)"],"where"=>[["col"=>"datumzukonceninespolehlivosti","op"=>"isnot","value"=>null]]]);
    $last = DB::qbr("data_mfcr_dic_bad_core",["cols"=>["c"=>"max(od)"]]);

    DB::u($statstable,"625408c1d25ac7c595aa008dcffc252d",["name"=>"Registr spolehlivosti plátců DPH","all"=>$allCompanies["c"],"deleted"=>$allVymaz["c"],"last"=>$last["c"]]);
    
}

/*
if($force || !DB::gr($statstable,"data_posta_datovaschranka")){
    echo "data_posta_datovaschranka\n";
    
    $allCompanies = DB::qbr("data_posta_datovaschranka",["cols"=>["c"=>"count(id2)"]]);
    $last = DB::qbr("data_posta_datovaschranka",["cols"=>["c"=>"max(od)"]]);

    DB::u($statstable,"data_posta_datovaschranka",["name"=>"Datové schránky","all"=>$allCompanies["c"],"last"=>$last["c"]]);
    
}

if($force || !DB::gr($statstable,md5("RES"))){
    echo "RES\n";

    $allRES = DB::qbr("data_firmy_ares02_core",["cols"=>["c"=>"count(id2)"]]);
    $allIco2Nace = DB::qbr("data_firmy_ares02_list_core",["cols"=>["c"=>"count(id2)"]]);
    $last = DB::qbr("data_firmy_ares02_list_core",["cols"=>["c"=>"max(od)"]]);

    DB::u($statstable,"RES",["name"=>"RES","all"=>$allRES["c"],"ico-comb"=>$allIco2Nace["c"],"last"=>$last["c"]]);
    
}
/**/

foreach($process  as $T=>$conf){
    echo "$T ".date("c")."\n";
    
    if($force || !DB::gr($statstable,$T)){
        $tt = str_replace("devczfast.","",$T);
        $tt = str_replace("devcz.","",$tt);
        
        
        $all = DB::qbr($T,["cols"=>["c"=>"count(id2)"]]);
        $icocol = "data-ico-clear";
        if(isset($conf["ico-col"])) $icocol = $conf["ico-col"];
        $allDistinctIco = DB::qbr($T,["distinct"=>true,"cols"=>["c"=>"count($icocol)"]]);
        $valueSum = ["c"=>""];
        if(isset($conf["sum-col"])){
            $valcol = $conf["sum-col"];
            $valueSum = DB::qbr($T,["cols"=>["c"=>"sum(`$valcol`)"]]);
        }
        
        
        $datecol = "data-date";
        if(isset($conf["date-col"])){
            $datecol = $conf["date-col"];
        }
        if(isset($conf["date-col-stats"])){
            $datecol = $conf["date-col-stats"];
        }
        $last = DB::qbr($T,["cols"=>["c"=>"max(`$datecol`)"]]);
        var_dump(DB::error());
        var_dump($valueSum);
        var_dump($last);
        
        DB::u($statstable,md5($tt),[
            "name"=>$conf["name"],
            "all"=>$all["c"],
            "distinct-ico"=>$allDistinctIco["c"],
            "valuesum"=>$valueSum["c"],
            "table"=>$tt,
            "last"=>$last["c"],
            ]);
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
