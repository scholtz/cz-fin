<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

echo "processing distribuce..\n";
$row = 0;
if (($handle = fopen("distribuce.csv", "r")) !== FALSE) {
    $n2k = [];
    while (($data = fgetcsv($handle, 16384, ",")) !== FALSE) {$row++;
        if($row==1){
            
            foreach($data as $k=>$v){
                $k2n[$k] = Texts::clear($v);
                $n2k[Texts::clear($v)] = $k;
            }
            
        }else{
            if($row % 100 == 0){echo ".";}
            if($row % 10000 == 0){echo $row."/".date("c")."\n";}
                                
            $update = [];
            foreach($data as $k=>$v){
                $update[$k2n[$k]] = $v;
            }
            $update["link_hash"] = md5($update["odkazkestazeni"]);
            DB::u("data_opendata_distribuce_core",md5($update["distribuce"]),$update);
        }
    }
}

$sady = [];
echo "processing sady..\n";
$row = 0;
if (($handle = fopen("datove-sady.csv", "r")) !== FALSE) {
    $n2k = [];
    while (($data = fgetcsv($handle, 16384, ",")) !== FALSE) {$row++;
        if($row==1){
            
            foreach($data as $k=>$v){
                $k2n[$k] = Texts::clear($v);
                $n2k[Texts::clear($v)] = $k;
            }
            
        }else{
            if($row % 100 == 0){echo ".";}
            if($row % 10000 == 0){echo $row."/".date("c")."\n";}
                                
            $update = [];
            foreach($data as $k=>$v){
                $update[$k2n[$k]] = $v;
            }
            
            DB::u("data_opendata_sady_core",md5($update["datova-sada"]),$update);
            $sady[$update["datova-sada"]] = $update;
        }
    }
}
Cron::end();
