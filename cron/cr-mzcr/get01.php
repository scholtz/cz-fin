<?php
use AsyncWeb\Cron\Cron;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
use AsyncWeb\Text\Texts;
require_once("/cron2/settingsCZ.php");

Cron::start(24*3600);



$path = "https://opendata.mzcr.cz/api/3/action/package_list";
$webstable = "data_mzcr_api_webs";
$text = Page::load($path,$webstable);
if(!$text){
    $data = json_decode($text = file_get_contents($path),true);
    Page::save($path,$text,$webstable);

}else{
    $data = json_decode($text,true);
}

foreach($data["result"] as $item){
    echo "$item\n";
    $path = "https://opendata.mzcr.cz/api/3/action/package_show?id=$item";
    $text = Page::load($path,$webstable);
    if(!$text){
        $text = file_get_contents($path);
        Page::save($path,$text,$webstable);
    }
    
    $res = json_decode($text,true);
    
    file_put_contents("01.json",$text);
    foreach($res["result"]["resources"] as $resource){
        if(strtolower($resource["format"]) == "csv"){   
            
            $table = Texts::clear_("data_mzcr_".$item);//."_".$resource["name"]);
            for($year = 2010;$year <= date("Y")+1;$year++){
                $table = Texts::clear_(str_replace($year,"",$table));
            }
            if(strpos(Texts::clear($resource["name"]),"polozky") !== false){
                $table .= "_polozky";
            }
            $table= substr($table,0,50);
            //var_dump();
            if($resource["url"]){
                $path = $resource["url"];
                $text = Page::load($path,$webstable);
                if(!$text){
                    $text = file_get_contents($path);
                    Page::save($path,$text,$webstable);
                }
                CSV2DB::Process($text,$table,["source"=>$path,"sourcename"=>$resource["name"],"publisher"=>$res["result"]["publisher_uri"],"licence"=>$resource["license_link"]]);
                /*
                file_put_contents("data.csv",$text);
                
                if($path != "https://opendata.mzcr.cz/data/azvcr/faktury/2018/faktury.csv" 
                && $path != "https://opendata.mzcr.cz/data/azvcr/faktury/2018/polozky.csv"){
                    var_dump($resource);
                    exit;
                }
                /**/
            }
            
        }
    }
}

class CSV2DB{
    
    public static function Process($text,$table,$add){
        $i = 0;
        $delimiter = ",";
        foreach(explode("\n",$text) as $line){$i++;
            if($i==1){
                // header
                
                $semi = count(explode(";",$line));
                $comma = count(explode(",",$line));
                $tab = count(explode("\t",$line));
                if($semi > $comma && $semi > $tab) $delimiter = ";";
                if($comma > $semi && $comma > $tab) $delimiter = ",";
                if($tab > $semi && $tab > $comma) $delimiter = "\t";

                $data = str_getcsv($line,$delimiter);
                foreach($data as $k=>$col){
                    $col = Texts::clear($col);
                    if($col == "id") $col = "identifier";
                    if($col == "id2") $col = "identifier2";
                    if($col == "od") $col = "od_od";
                    if($col == "do") $col = "do_do";
                    $n2k[$k] = $col;
                }
            }else{
                $data = str_getcsv($line,$delimiter);
                if(count($data) < 3) continue;
                $update = [];
                $id = "";
                foreach($data as $k=>$value){
                    $update[$n2k[$k]] = $value;
                    $id = md5($id.$k.$value);
                }
                foreach($add as $c=>$v){
                    $update[$c] = $v;
                }
                DB::u($table,$id,$update);
            }
        }
        
    }
}

echo "\nDONE ".date("c")."\n";
Cron::end();