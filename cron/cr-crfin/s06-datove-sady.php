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
require_once("/ocz/vhosts/cz-fin.com/prod01/src/AT/src/Classes/PageBuilder.php");
$pagebuilder = new \AT\Classes\PageBuilder();
$process = $pagebuilder->makeConfig();


$data = [];
//$data["ČESKÁ NÁRODNÍ BANKA"]["Seznam osob oprávněných provozovat směnárenskou činnost na území České republiky"] = true;

echo "data_arescz_company_core ".date("c")."\n";

$row = DB::qbr("data_mze_dpb_core",["cols"=>["c"=>"max(od)"]]);
$data["Ministerstvo zemědělství"]["Veřejný export dat LPIS"] = $row["c"];
$row = DB::qbr("data_posta_datovaschranka",["cols"=>["c"=>"max(od)"]]);
$data["Ministerstvo vnitra"]["Datové schránky"] = $row["c"];
$row = DB::qbr("data_smlouvy_core",["cols"=>["c"=>"max(od)"]]);
$data["Ministerstvo vnitra"]["Registr smlouv"] = $row["c"];
$row = DB::qbr("data_firmy_ares02_core",["cols"=>["c"=>"max(od)"]]);
$data["Český statistický úřad"]["Registr ekonomických subjektů"]=$row["c"];
$data["Ministerstvo obrany"]["Uhrazené faktury"] = 0;
$row = DB::qbr("data_arescz_company_core",["cols"=>["c"=>"max(od)"]]);
$data["Ministerstvo financí"]["ARES Full Snapshot"]=$row["c"];
$last = DB::qbr("data_mfcr_dic_bad_core",["cols"=>["c"=>"max(od)"]]);
$data["Ministerstvo financí"]["Registr spolehlivosti plátců DPH"]=$last["c"];


foreach($process  as $T=>$conf){
    
    $datecol = "data-date";
    if(isset($conf["date-col"])){
        $datecol = $conf["date-col"];
    }
    if(isset($conf["date-col-stats"])){
        //$datecol = $conf["date-col-stats"];
    }

    $res = DB::query($q = "update $T set `$datecol` = null where `$datecol` > '".time()."'");
    echo "\nFix: ".DB::num_rows($res)." $q\n";
    var_dump(DB::error());
    $res = DB::qb($T,["distinct"=>true,"cols"=>["source","c"=>"max(`$datecol`)"],"groupby"=>["source"]]);
    echo "$T ".date("c")."\n";
    while($row=DB::f($res)){
        echo $row["source"]."\n";
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
        /*
        if($sada["poskytovatel"] == "ČESKÁ NÁRODNÍ BANKA"){
            var_dump($sada);
            var_dump($distribuce);
            var_dump($row);
            var_dump($datecol);
            exit;
        }/**/
        
        if($row["c"] > time()){
            //var_dump($row["c"]);
            var_dump($T);
            var_dump($datecol);
            var_dump($row);
            var_dump(date("c",$row["c"]));
            exit;
        }
        if(isset($data[$sada["poskytovatel"]][$sada["nazev"]])){
            $data[$sada["poskytovatel"]][$sada["nazev"]] = max($row["c"],$data[$sada["poskytovatel"]][$sada["nazev"]]);
        }elsE{
            $data[$sada["poskytovatel"]][$sada["nazev"]] = $row["c"];
        }
    }
}

$ret = '<h1>Seznam použitých datových zdrojů</h1>
<div class="row">';
foreach($data as $k=>$v){
    $ret.='<br><div class="col-md-3"><div class="card"><h3 class="card-header">'.$k.'</h3>';
    $ret.= '<ul class="list-group">';
    arsort($v);
    foreach($v as $k2=>$t){
        if($t > 1){
            DB::u("data_czfin_datasetsstats",md5($k.$k2),["group"=>$k,"name"=>$k2,"date"=>$t]);
            $ret.='<li class="list-group-item d-flex justify-content-between align-items-center">'.$k2.' <span class="badge badge-primary badge-pill">'.date("d.m.Y",$t).'</span></li>';
        }
    }
    $ret.= '</ul></div></div>';
}
$ret.='</div>';

//$maintable = "data_czfin_pages";
//DB::u($maintable,"datove-sady",["page"=>$ret]);

$r = DB::g($table = "data_czfin_datasetsstats");
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
