<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$webstable = "data_opendata_data_webs";

$work = true;
$i = 0;

    $res = DB::qb($webstable,[
        "cols"=>["id"],
        ]);
$cc = DB::num_rows($res);

while($work){  

    $basicwhere = ["spracovany"=>"0"];
    $where = $basicwhere;
    $work = true;
      
    $res = DB::qb($webstable,[
        "limit"=>10000,
        "cols"=>["id","id2","web","data"],
        //"where"=>$where,
        "order"=>["id"=>"asc"],
        ]);
    $c = DB::num_rows($res);
    if(!$c) $work = false;

    while($row=DB::f($res)){
        
        $i++;
        if($i%10==1) echo ".";
        if($i%1000==1) echo "\n$i/$cc/".date("c")."";
        
        $where = [["col"=>"id","op"=>"gt","value"=>$row["id"]]];

        
        $data = gzuncompress($row["data"]);
        
        if(strpos($clear,"materidouska")){
            file_put_contents("materi.html",$data);
            var_dump($row["web"]);
        }
        if(strpos($clear,"mateřídouška")){
            file_put_contents("materi.html",$data);
            var_dump($row["web"]);
        }
        if(strpos($clear,"mateří douška")){
            file_put_contents("materi.html",$data);
            var_dump($row["web"]);
        }
        if(strpos($clear,"materi douska")){
            file_put_contents("materi.html",$data);
            var_dump($row["web"]);
        }
     
    }
}
Cron::end();
