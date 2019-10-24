<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;

require_once("/cron2/settingsCZ.php");


Cron::start(24*3600);
echo "started ".date("c");

$force =true;
require_once("/ocz/vhosts/cz-fin.com/prod01/src/AT/src/Classes/PageBuilder.php");
$pagebuilder = new \AT\Classes\PageBuilder();
$process = $pagebuilder->makeConfig();
$table="data_czfin_datasets";
DB::query("truncate table $table");

$data = [];
//$data["ČESKÁ NÁRODNÍ BANKA"]["Seznam osob oprávněných provozovat směnárenskou činnost na území České republiky"] = true;

echo "data_arescz_company_core ".date("c")."\n";

$saveto = "/ocz/vhosts/cz-fin.com/prod01/datasety/";
foreach($process  as $T=>$conf){
    $tt = str_replace("devczfast.","",$T);
    $tt = str_replace("devcz.","",$tt);

    echo "\nprocessing $T ".date("c")."\n";
    
    $i = 0;
    $out = "";
    file_put_contents($file=$saveto.$tt.".csv","");
    $id = null;
    $work = true;
    $qb = [];//["limit"=>100000000,"order"=>["id"=>"asc"]];
    while($work){
        $work = false;
        
        //var_dump($qb);
        $res = DB::qb($T,$qb);
        //var_dump(DB::num_rows($res));
        while($row=DB::f($res)){
            //$work = true;
            if($i%100000==0) echo "\n$i/".$row["id"]."/".date("c")."";
            if($i%1000==0) echo ",";
            $i++;
            if($id = $row["id"]){
                //$qb["where"]["id"] = [["col"=>"id","op"=>"gt","value"=>$id]];
            }
//            var_dump($id);
            if($i == 1){
                $line = "";
                foreach($row as $column=>$value){
                    switch($column){
                        case "id":
                        case "od":
                        case "do":
                        case "lchange":
                        case "edited_by":
                        
                        break;
                        case "id2":
                            if($conf["ico-col"] == "id2"){
                                $line.='"ICO",';
                            }
                        break;
                        
                        default:
                            $line.='"'.str_replace('"','""',$column).'",';
                        break;
                    }
                }
                $line = rtrim($line,",");
                file_put_contents($file,"$line\n",FILE_APPEND);
            }
            $line = "";
            foreach($row as $column=>$value){
                switch($column){
                    case "id":
                    case "od":
                    case "do":
                    case "lchange":
                    case "edited_by":
                    
                    break;
                    case "id2":
                        if($conf["ico-col"] == "id2"){
                            $line.='"'.str_replace('"','""',$value).'",';
                        }
                    break;
                    default:
                        $line.='"'.str_replace('"','""',$value).'",';
                    break;
                }
            }
            $line = rtrim($line,",");
            file_put_contents($file,"$line\n",FILE_APPEND);
            unset($line);
        }
    }
    
    $size = filesize($file);
    var_dump(`gzip -f -9 $file`);
    $size2 = filesize($file.".gz");
    $md5 = md5_file($file.".gz");
    
    $update = ["config"=>$T,"tt"=>$tt,"file"=>$file.".gz","size_uncompressed"=>$size,"size_compressed"=>$size2,"md5"=>$md5];
    
    $old = DB::qbr($table,["where"=>["id2"=>md5($tt)],"cols"=>["md5"]]);
    if($old["md5"] != $md5){
        $etag = sprintf( '"%s-%s"', time(), crc32( $md5 ) );
        $update["etag"]=$etag;
    }

    DB::u($table,md5($tt),$update);
}




$r = DB::g($table);
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
