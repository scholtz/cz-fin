<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$maintable = "data_czfin_pages";
$ratingtable = "data_czfin_rating";
$arestable = "data_arescz_company_core";

$work = true;
$i = 0;

echo "calculating total rows: ";
$basicwhere = [["col"=>"datumvymazu","op"=>"is","value"=>null]];
//$basicwhere[] = ["col"=>"ico","op"=>"eq","value"=>"25573306"];
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

echo "\nidem spracovat faktury do ramky:";
$invdatares = DB::qb("data_all_core_faktury",[
    "order"=>["data-date"=>"desc"],
]
);

$invtables = [];
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
    
    if(!isset($invtables[$inv["data-ico-clear"]])){
        $invtables[$inv["data-ico-clear"]] = [];
    }else{
        if(isset($invtables[$inv["data-ico-clear"]]) && count($invtables[$inv["data-ico-clear"]]) >= 50) continue;
    }
    $invtables[$inv["data-ico-clear"]][] = $inv;
    
    $ii++;
    if($ii%100==1) echo "I";
    if($ii%10000==1) echo "\n$ii/$cc/".date("c")."";
}
echo count($invtables)." spolocnosti ma fakturu\n";


echo "\nidem spracovat objednavky do ramky:";
$invdatares = DB::qb("data_all_core_objednavky",[
    "order"=>["data-date"=>"desc"],
]
);

$objtables = [];
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
    
    if(!isset($objtables[$inv["data-ico-clear"]])){
        $objtables[$inv["data-ico-clear"]] = [];
    }else{
        if(isset($objtables[$inv["data-ico-clear"]]) && count($objtables[$inv["data-ico-clear"]]) >= 50) continue;
    }
    $objtables[$inv["data-ico-clear"]][] = $inv;
    
    $ii++;
    if($ii%100==1) echo "I";
    if($ii%10000==1) echo "\n$ii/$cc/".date("c")."";
}
echo count($objtables)." spolocnosti ma objednavku\n";


 $process = [
    "data_firmy_ares02_core"=>[
        "context"=>"res",    
        "name"=>"Register ekonomických subjektu",
        "ico-col"=>"id2",
        "date-col"=>"od",
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
    ],
    ];
$ramtables = [];
foreach($process as $table=>$conf){

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

while($work){  

    echo "fetching information about companies ".date("c")."\n";
    $work = true;
    $res = DB::qb($arestable,[
        "limit"=>10000,
        "cols"=>["id","ico","obchodnifirma","sidlo","datumzapisu","clear",],
        "where"=>$where,
        "order"=>["id"=>"asc"],
        ]);
    $c = DB::num_rows($res);
    if(!$c) $work = false;

    while($row=DB::f($res)){
        
        $i++;
        if($i%10==1) echo ".";
        if($i%1000==1) echo "\n$i/$cc/".date("c")."";
        

        $where = $basicwhere;
        $where[] = ["col"=>"id","op"=>"gt","value"=>$row["id"]];

        if(isset($done[$row["ico"]])) continue;
        
        
        $sidlo = DB::gr("data_arescz_adr_core",["id2"=>$row["sidlo"]]);
        $body = 0;
        if(strpos($row["clear"],"v-likvi") !== false){
            $body+=100;
        }
        if($time = strtotime($row["datumzapisu"])){
            if($time < strtotime("1900")){
                
            }elseif($time < strtotime("1990-01-01")){
                $body+=10;
            }elseif($time < strtotime("1995-01-01")){
                $body+=200;
            }elseif($time < strtotime("2000-01-01")){
                $body+=100;
            }elseif($time < strtotime("2000-01-01")){
                $body+=50;
            }elseif($time < strtotime("2010-01-01")){
                $body+=20;
            }elseif($time < strtotime("2015-01-01")){
                $body+=10;
            }elseif($time < strtotime("2018-01-01")){
                $body+=5;
            }
        }
        if(strlen($row["clear"]) < 10){
                $body+=107;
        }elseif(strlen($row["clear"]) < 15){
                $body+=53;
        }elseif(strlen($row["clear"]) < 20){
                $body+=21;
        }
        
        $page = '<h1>'.$row["obchodnifirma"].'</h1>';
        $page .= '<table class="table table-striped table-hover table-sm table-bordered">';
        
        if($sidlo["text"]){
            $page .= '<tr><th>Adresa</th><td>'.$sidlo["text"].'</td></tr>';
        }
        if($row["datumzapisu"]){
            $page .= '<tr><th>Datum zápisu</th><td>'.date("d.m.Y",strtotime($row["datumzapisu"])).'</td></tr>';
        }
        if($row["datumvzniku"]){
            $page .= '<tr><th>Datum vzniku</th><td>'.$row["datumvzniku"].'</td></tr>';
        }
        if($row["datumvymazu"]){
            $page .= '<tr><th>Datum výmazu</th><td>'.date("d.m.Y",strtotime($row["datumvymazu"])).'</td></tr>';
        }
        
        $page .= '</table>';
        
          if(true){ ######################## data_firmy_ares02_list_core	
            
            $invdatares = DB::qb("data_firmy_ares02_list_core",[
                "where"=>["ico"=>$row["ico"]],
            ]
            );
            $spoluCiastka=0;
            $count = 0;
            $table = [];
            $cols = [];
            while($invdata = DB::f($invdatares)){
                $count++;
                $spoluCiastka+=$invdata["data-pokuta-v-kc"];
                
                $datarow = [];
                foreach($invdata as $col=>$value){
                    if(!trim($value)) continue;
                    if($col == "id") continue;
                    if($col == "id2") continue;
                    if($col == "od") continue;
                    if($col == "do") continue;
                    if($col == "lchange") continue;
                    if($col == "edited_by") continue;
                    if(strpos($col,"data-") === 0) continue;
                    $datarow[$col] = $value;
                    
                    if($col == "source") continue;
                    $cols[$col] = true;
                }
                if($datarow){
                    $table[] = $datarow;
                }
            }
            $cols["source"] = true;
            
            if($count > 0){
                $body += 200;
            }
            
            if($count > 20 ){
                $body += 2;
            }else
            if($count > 10 ){
                $body += 10;
            }else
            if($count > 5 ){
                $body += 20;
            }else
            if($count > 3 ){
                $body += 50;
            }else
            if($count > 0 ){
                $body += 100;
            }
            
            if($table){
                $page .= '<div class="card"><h5 class="card-header">
                <a data-toggle="collapse" href="#nace" aria-expanded="true" aria-controls="nace" id="heading-nace" class="d-block">
                    <i class="fa fa-chevron-down pull-right"></i>
                    Činnosti
                </a>
                </h5>
                <div id="nace" class="collapse show" aria-labelledby="heading-nace">
                <div class="table-responsive">
                <table class="table table-striped table-hover table-sm table-bordered scroll-table"><thead><tr>';
                foreach($cols as $col=>$t){
                    $page.='<th>'.ucfirst(str_replace("-"," ",$col)).'</th>';
                }
                $page .= '</tr></thead><tbody class="tbody">';
                foreach($table as $datarow){
                    $page .= '<tr>';
                    foreach($cols as $col=>$t){
                        $value = "";
                        if(isset($datarow[$col])){
                            $value = $datarow[$col];
                        }
                        if($col == "source"){
                            $value = "https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_res.cgi?ico=".$row["ico"];
                            $value = '<a class="btn btn-light" target="_blank" href="'.$value.'">[ZDROJ]</a>';
                        }
                        $page .= '<td title="'.$col.'">'.$value.'</td>';
                    }
                    $page .= '</tr>';
                }
                $page .='</tbody></table></div></div></div>';
            }
        }
        
        if(true){ ######################## FAKTURY
            /*
            $invdatares = DB::qb("data_all_core_faktury",[
                "where"=>["data-ico-clear"=>$row["ico"]],
                "order"=>["data-date"=>"desc"],
            ]
            );
            /**/
            $spoluCiastka=0;
            $count = 0;
            $table = [];
            $cols = [];
            if(isset($invtables[$row["ico"]]))
            while($invdata = array_pop($invtables[$row["ico"]])){
                $count++;
                
                $spoluCiastka+=$invdata["data-castka-s-dph"];
                
                $datarow = [];
                foreach($invdata as $col=>$value){
                    if(!trim($value)) continue;
                    if($col == "id") continue;
                    if($col == "id2") continue;
                    if($col == "od") continue;
                    if($col == "do") continue;
                    if($col == "lchange") continue;
                    if($col == "edited_by") continue;
                    if(strpos($col,"data-") === 0) continue;
                    $datarow[$col] = $value;
                    
                    if($col == "source") continue;
                    $cols[$col] = true;
                }
                if($datarow){
                    $table[] = $datarow;
                }
            }
            $cols["source"] = true;
            
            if($count > 1000){
                $body += 222;
            }elseif($count > 100){
                $body += 121;
            }elseif($count > 0){
                $body += 53;
            }
            
            if($spoluCiastka > 1000000000){
                $body += 231;
            }elseif($spoluCiastka > 1000000){
                $body += 83;
            }elseif($spoluCiastka > 10000){
                $body += 33;
            }
            
            
            if($table){
                $page .= '<div class="card"><h5 class="card-header">
                <a data-toggle="collapse" href="#invoices" aria-expanded="false" aria-controls="invoices" id="heading-invoices" class="d-block">
                    <i class="fa fa-chevron-down pull-right"></i>
                    Faktury
                </a>
                </h5>
                
                <div id="invoices" class="collapse" aria-labelledby="heading-invoices">
                <div class="table-responsive">
                <table class="table table-striped table-hover table-sm table-bordered scroll-table"><thead><tr>';
                foreach($cols as $col=>$t){
                    $page.='<th>'.ucfirst(str_replace("-"," ",$col)).'</th>';
                }
                $page .= '</tr></thead><tbody class="tbody">';
                foreach($table as $datarow){
                    $page .= '<tr>';
                    foreach($cols as $col=>$t){
                        $value = "";
                        if(isset($datarow[$col])){
                            $value = $datarow[$col];
                        }
                        if($col == "source"){
                            $value = '<a class="btn btn-light" target="_blank" href="'.$value.'">[ZDROJ]</a>';
                        }
                        $page .= '<td title="'.$col.'">'.$value.'</td>';
                    }
                    $page .= '</tr>';
                }
                $page .='</tbody></table></div></div></div>';
            }
        }
        
        if(true){ ######################## OBJEDNAVKY
            
            
            $spoluCiastka=0;
            $count = 0;
            $table = [];
            $cols = [];
            if(isset($objtables[$row["ico"]]))
            while($invdata = array_pop($objtables[$row["ico"]])){
                $count++;
                $spoluCiastka+=$invdata["data-celkova-castka"];
                
                $datarow = [];
                foreach($invdata as $col=>$value){
                    if(!trim($value)) continue;
                    if($col == "id") continue;
                    if($col == "id2") continue;
                    if($col == "od") continue;
                    if($col == "do") continue;
                    if($col == "lchange") continue;
                    if($col == "edited_by") continue;
                    if(strpos($col,"data-") === 0) continue;
                    $datarow[$col] = $value;
                    
                    if($col == "source") continue;
                    $cols[$col] = true;
                }
                if($datarow){
                    $table[] = $datarow;
                }
            }
            $cols["source"] = true;
            
            if($count > 1000){
                $body += 230;
            }elseif($count > 100){
                $body += 99;
            }elseif($count > 0){
                $body += 23;
            }
            
            if($spoluCiastka > 1000000000){
                $body += 221;
            }elseif($spoluCiastka > 1000000){
                $body += 44;
            }elseif($spoluCiastka > 1000){
                $body += 30;
            }
            
            
            if($table){
                $page .= '<div class="card"><h5  class="card-header">
                <a data-toggle="collapse" href="#orders" aria-expanded="false" aria-controls="orders" id="heading-orders" class="d-block">
                    <i class="fa fa-chevron-down pull-right"></i>
                    Objednávky
                </a>
                </h5>
                <div id="orders" class="collapse" aria-labelledby="heading-orders">
                <div class="table-responsive">
                <table class="table table-striped table-hover table-sm table-bordered scroll-table"><thead><tr>';
                foreach($cols as $col=>$t){
                    $page.='<th>'.ucfirst(str_replace("-"," ",$col)).'</th>';
                }
                $page .= '</tr></thead><tbody class="tbody">';
                foreach($table as $datarow){
                    $page .= '<tr>';
                    foreach($cols as $col=>$t){
                        $value = "";
                        if(isset($datarow[$col])){
                            $value = $datarow[$col];
                        }
                        if($col == "source"){
                            $value = '<a class="btn btn-light" target="_blank" href="'.$value.'">[ZDROJ]</a>';
                        }
                        $page .= '<td title="'.$col.'">'.$value.'</td>';
                    }
                    $page .= '</tr>';
                }
                $page .='</tbody></table></div></div></div>';
            }
        }
     
     
     foreach($process as $T=>$dataconfig){ ######################## data_all_core_prostory
            $icocol = "data-ico-clear";
            $datecol = "data-date";
            if(isset($dataconfig["ico-col"])) $icocol = $dataconfig["ico-col"];
            if(isset($dataconfig["date-col"])) $datecol = $dataconfig["date-col"];
            /*
            $invdatares = DB::qb($T,$c = [
                "where"=>$w = [$icocol=>$row["ico"]],
                "order"=>$o = [$datecol=>"desc"],
            ]
            );/**/
            
            $spoluCiastka=0;
            $count = 0;
            $table = [];
            $cols = [];
            $hassource = false; 
            if(isset($ramtables[$T][$row["ico"]]))
            while($invdata = array_pop($ramtables[$T][$row["ico"]])){
                $count++;
                
                $datarow = [];
                foreach($invdata as $col=>$value){
                    if(!trim($value)) continue;
                    if($col == "id") continue;
                    if($col == "id2") continue;
                    if($col == "od") continue;
                    if($col == "do") continue;
                    if($col == "lchange") continue;
                    if($col == "edited_by") continue;
                    if(strpos($col,"data-") === 0) continue;
                    if(strlen($value) > 50) continue;
                    $datarow[$col] = $value;
                    
                    if($col == "source") {$hassource = true;continue;}
                    $cols[$col] = true;
                }
                if($datarow){
                    $table[] = $datarow;
                }
            }
            
            if($hassource){
                $cols["source"] = true;
            }
            if($count > 100){
                $body += 130;
            }elseif($count > 10){
                $body += 39;
            }elseif($count > 0){
                $body += 13;
            }
            
            
            
            if($table){
                $page .= '<div class="card"><h5  class="card-header">
                <a data-toggle="collapse" href="#'.$dataconfig["context"].'" aria-expanded="false" aria-controls="'.$dataconfig["context"].'" id="heading-'.$dataconfig["context"].'" class="d-block">
                    <i class="fa fa-chevron-down pull-right"></i>
                    '.$dataconfig["name"].'
                </a>
                </h5>
                <div id="'.$dataconfig["context"].'" class="collapse" aria-labelledby="heading-'.$dataconfig["context"].'">
                <div class="table-responsive">
                <table class="table table-striped table-hover table-sm table-bordered scroll-table"><thead><tr>';
                foreach($cols as $col=>$t){
                    $page.='<th>'.ucfirst(str_replace("-"," ",$col)).'</th>';
                }
                $page .= '</tr></thead><tbody class="tbody">';
                foreach($table as $datarow){
                    $page .= '<tr>';
                    foreach($cols as $col=>$t){
                        $value = "";
                        if(isset($datarow[$col])){
                            $value = $datarow[$col];
                        }
                        
                        if($col == "source" && $value){
                            $value = '<a class="btn btn-light" target="_blank" href="'.$value.'">[ZDROJ]</a>';
                        }
                        $page .= '<td title="'.$col.'">'.$value.'</td>';
                    }
                    $page .= '</tr>';
                }
                $page .='</tbody></table></div></div></div>';
            }
        }
     
        
     

        DB::u($maintable,$row["ico"],["page"=>$page]);
        DB::u($ratingtable,$row["ico"],["body"=>$body,"obchodnifirma"=>$row["obchodnifirma"],"clear"=>$row["clear"]]);
    }
}



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
    DB::query("drop table `out`.`$table`");
    echo DB::error();
    echo "\nrenaming table `${table}_tmp$rand` TO `out`.`$table`\n";
    DB::query("RENAME TABLE `out`.`${table}_tmp$rand` TO `out`.`$table`");
    echo DB::error();
    echo "DONE";	
}

Cron::end();
