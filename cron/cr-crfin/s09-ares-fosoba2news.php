<?php

use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
require_once("/cron2/settingsCZ.php");
echo "starting.. ".date("c")."\n";

Cron::start(24*3600);


$res = DB::qb("devcz.data_arescz_fosoba_core",["cols"=>["id","id2","jmeno","prijmeni"]]);
$config = [];
$config["cols"][$colname="newscount"]["type"] = "int";
$config["keys"][] = "newscount";
$t = true;

$balancer = new \AsyncWeb\System\CPU\LoadBalancer(0.2,5000,0.3,50000,0.5,2000000);
$cc = DB::num_rows($res);
$i = 0;
$data = [];
while($row=DB::f($res)){
    $i++;
    if($i%100==0) echo ".";
	if($i%10000==0) echo "$i/".$row["id"]."/$cc/".date("c")."\n";
    $balancer->wait();
    
    $clear = Texts::clear($row["jmeno"]." ".$row["prijmeni"]);
    if(isset($data[$clear])){
        $count = $data[$clear];
    }else{
        $n = substr_count($clear,"-");
        $count = 0;
        if($n == 1){
            if($c = DB::qbr("dev02fast.cs_word_combinations_2_out",["where"=>["id2"=>md5($clear)],"cols"=>["count"]])){
                $count += $c["count"];
            }
            if($c = DB::qbr("dev02fast.sk_word_combinations_2_out",["where"=>["id2"=>md5($clear)],"cols"=>["count"]])){
                $count += $c["count"];
            }
        }else if($n == 2){
            if($c = DB::qbr("dev02fast.cs_word_combinations_3_out",["where"=>["id2"=>md5($clear)],"cols"=>["count"]])){
                $count += $c["count"];
            }
            if($c = DB::qbr("dev02fast.sk_word_combinations_3_out",["where"=>["id2"=>md5($clear)],"cols"=>["count"]])){
                $count += $c["count"];
            }
        }
        
        $data[$clear] = $count;
    }
//    if($count > 0){
        DB::u("devcz.data_arescz_fosoba_core",$row["id2"],["newscount"=>$count],$config,$t,$t);
        $config = false;
        $t = false;
//    }
}


Cron::end();

echo "finished ".date("c")."\n";

