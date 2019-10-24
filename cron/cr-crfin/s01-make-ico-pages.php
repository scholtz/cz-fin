<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");
require_once("/ocz/vhosts/cz-fin.com/prod01/src/AT/src/Classes/PageBuilder.php");


Cron::start(24*3600);
echo "started ".date("c");

$maintable = "data_czfin_pages";
$ratingtable = "data_czfin_rating";
$arestable = "data_arescz_company_core";

$work = true;
$i = 0;

echo "calculating total rows: ";
$basicwhere = [["col"=>"datumvymazu","op"=>"is","value"=>null]];
//$basicwhere[] = ["col"=>"ico","op"=>"eq","value"=>"29025648"];
//$basicwhere[] = ["col"=>"ico","op"=>"eq","value"=>"00642215"];//objednavky

    $res = DB::qb($arestable,[
        "cols"=>["id"],
        "where"=>$basicwhere,
        ]);
$where = $basicwhere;        
$cc = DB::num_rows($res);
echo "$cc\n";

/*
echo "idem skontrolovat uz spracovane stranky: ";
$done = [];
$res = DB::qb($maintable,["cols"=>["id2"]]);
while($row=DB::f($res)){
    $done[$row["id2"]] = true;
}
echo count($done)." je uz spracovanych\n";
/**/

$pagebuilder = new \AT\Classes\PageBuilder();


$process = $pagebuilder->makeConfig();
 
$ramtables = [];
foreach($process as $table=>$conf){
    if($conf["type"] != "ram") continue;

    echo "\nidem spracovat $table do ramky:";
    $datecol = "data-date";
    if(isset($conf["date-col"])) $datecol = $conf["date-col"];
    $icocol = "data-ico-clear";
    if(isset($conf["ico-col"])) $icocol = $conf["ico-col"];
    
    $invdatares = DB::qb($table,[
        "order"=>[$datecol=>"desc"],
    ]
    );

    
    $ii = 0;
    while($inv = DB::f($invdatares)){
        unset($inv["id"]);
        unset($inv["id2"]);
        unset($inv["od"]);
        unset($inv["do"]);
        unset($inv["lchange"]);
        unset($inv["edited_by"]);
        
        foreach($inv as $k=>$v){
            if($v === null){
                unset($inv[$k]);
            }
        }
        
        if(!isset($ramtables[$table][$inv[$icocol]])){
            $ramtables[$table][$inv[$icocol]] = [];
        }else{
            if(isset($ramtables[$table][$inv[$icocol]]) && count($ramtables[$table][$inv[$icocol]]) >= 100) continue;
        }
        $ramtables[$table][$inv[$icocol]][] = $inv;
        
        $ii++;
        if($ii%100==1) echo ",";
        if($ii%10000==1) echo "\n$ii/$cc/".date("c")."";
    }
    echo count($ramtables[$table])."\n";

}

echo "loading nacedb ".date("c").":";
$res = DB::qb("sknace",["cols"=>["id4","id5cz"]]);
while($row=DB::f($res)){
    $nacedb[$row["id4"]] = $row;
}
echo " ".count($nacedb)." ".date("c")."done\n";


while($work){  

    echo "fetching information about companies ".date("c")."\n";
    $work = true;
    $res = DB::qb($arestable,[
        "limit"=>10000,
        "cols"=>["id","id2","ico","obchodnifirma","sidlo","datumzapisu","clear",],
        "where"=>$where,
        "order"=>["id"=>"asc"],
        ]);
    $c = DB::num_rows($res);
    if(!$c) $work = false;

    while($row=DB::f($res)){
        $i++;
        if($i%10==1) echo ".";
        if($i%1000==1) echo "\n$i/$cc/".date("c")."";
        //if(isset($done[$row["ico"]])) continue;
        $where = $basicwhere;
        $where[] = ["col"=>"id","op"=>"gt","value"=>$row["id"]];
        
        $ret = $pagebuilder->makeCompanyPage($row,$ramtables,$nacedb);
        
        $clear = $row["clear"];
        if(!$clear){
            $clear = Texts::clear($row["obchodnifirma"]);
            DB::u($arestable,$row["id2"],["clear"=>$clear],false,false,false);
        }

        DB::u($maintable,$row["ico"],["page"=>$ret["text"]]);
        DB::u($ratingtable,$row["ico"],["body"=>$ret["points"],"obchodnifirma"=>$row["obchodnifirma"],"clear"=>$clear,"email"=>$ret["email"],"tel"=>$ret["tel"],"web"=>$ret["web"],"size"=>$ret["size"],"nace"=>$ret["nace"],"kraj"=>$ret["kraj"],"okres"=>$ret["okres"],"mesto"=>$ret["mesto"]]);
    }
}


echo "finished.. ".date("c")." going to copy to output\n"; 
$r = DB::qb($table = $maintable,["limit"=>"1"]);
DB::query("delete from $maintable where do > 0");
DB::query("optimize table $maintable");
if(DB::num_rows($r) > 0){
    
    $rand = rand(10000,99999);
    echo "\ncopying to prod tmp table schema `out`.`${table}_tmp$rand`";
    DB::query("CREATE TABLE `out`.`${table}_tmp$rand` LIKE `devcz`.`$table`");
    echo DB::error();
    echo "\ncopying to prod tmp table data";
    DB::query("INSERT INTO `out`.`${table}_tmp$rand` SELECT * FROM `devcz`.`$table` where do = 0");
    echo DB::error();
    echo "\ndropping table";
    DB::query("drop table `out`.`$table`");
    echo DB::error();
    echo "\nrenaming table `${table}_tmp$rand` TO `out`.`$table`";
    DB::query("RENAME TABLE `out`.`${table}_tmp$rand` TO `out`.`$table`");
    echo DB::error();
    echo "\nDONE ".date("c")."\n";	
}

echo "finished.. ".date("c")." going to copy to output\n"; 
DB::query("delete from $ratingtable where do > 0");
DB::query("optimize table $ratingtable");
$r = DB::qb($table = $ratingtable,["limit"=>"1"]);
if(DB::num_rows($r) > 0){
    
    $rand = rand(10000,99999);
    echo "\ncopying to prod tmp table schema `out`.`${table}_tmp$rand`";
    DB::query("CREATE TABLE `out`.`${table}_tmp$rand` LIKE `devcz`.`$table`");
    echo DB::error();
    echo "\ncopying to prod tmp table data";
    DB::query("INSERT INTO `out`.`${table}_tmp$rand` SELECT * FROM `devcz`.`$table` where do = 0");
    echo DB::error();
    echo "\ndropping table";
    DB::query("drop table `out`.`$table`");
    echo DB::error();
    echo "\nrenaming table `${table}_tmp$rand` TO `out`.`$table`";
    DB::query("RENAME TABLE `out`.`${table}_tmp$rand` TO `out`.`$table`");
    echo DB::error();
    echo "\nDONE ".date("c")."\n";	
}
Cron::end();
