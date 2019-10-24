<?php

namespace AT\Classes;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;
use AsyncWeb\Text\Texts;

class News{
    
    public function getAllNews($count){
        $tables = [
            "dev02fast.cz_spravy_texts_clean","dev02fast.sk_spravy_texts_clean","dev02fast.en_spravy_texts_clean",
        ];
        
        $allnewsByTime = [];
        $done = [];
        foreach($tables as $table){
            $res = DB::qb($table,["limit"=>$count,"order"=>["time"=>"desc"]]);
            while($item = DB::f($res)){
                $web = $item["web"];
                if($pos = strpos($web,"#")){
                    $web = substr($web,0,$pos);
                    $item["web"] = $web;
                }
                if($pos = strpos($web,"?utm_source")){
                    $web = substr($web,0,$pos);
                    $item["web"] = $web;
                }
                $web = str_replace("http://","",$web);
                $web = str_replace("https://","",$web);
                $web = substr($web,0,strpos($web,"/"));
                $item["Source"] = $web;
                
                $arr = explode(".",$web);
                switch($arr[count($arr)-1]){
                    case "sk":
                        $item["lang"] = "sk";
                    break;
                    case "cz":
                        $item["lang"] = "cs";
                    break;
                    case "default":
                        $item["lang"] = "en";
                    break;
                }
                

                if(isset($done[$item["web"]])) continue;
                $done[$item["web"]] = true;
                if(!isset($allnewsByTime[$item["time"]])){
                    $allnewsByTime[$item["time"]] = [];
                }
                
                $allnewsByTime[$item["time"]][] = $item;
            
            }
        }
        krsort($allnewsByTime);
        return $allnewsByTime;
    }
    public function getNewsByTime($search){
        $tables = [
            "dev02fast.cs_word_combinations_1_out","dev02fast.cs_word_combinations_2_out","dev02fast.cs_word_combinations_3_out",
            "dev02fast.sk_word_combinations_1_out","dev02fast.sk_word_combinations_2_out","dev02fast.sk_word_combinations_3_out",
        ];
        
        $allnewsByTime = [];
        $done = [];
        foreach($tables as $table){
            $row = DB::qbr($table,["where"=>["id2"=>md5($search)]]);
            /*
            if(URLParser::v("debug")){
                if($table=="dev02fast.sk_word_combinations_1_out"){
                    var_dump(base64_decode($row["data"]));
                    var_dump($row);exit;
                }
            }/**/
            if($row && ($news1 = json_decode(base64_decode($row["data"]),true))){
                
            //var_dump($row)
                //var_dump($table);
                foreach($news1 as $k=>$item){
                    $web = $item["web"];
                    if($pos = strpos($web,"#")){
                        $web = substr($web,0,$pos);
                        $item["web"] = $web;
                    }
                    if($pos = strpos($web,"?utm_source")){
                        $web = substr($web,0,$pos);
                        $item["web"] = $web;
                    }
                    $web = str_replace("http://","",$web);
                    $web = str_replace("https://","",$web);
                    $web = substr($web,0,strpos($web,"/"));
                    $item["Source"] = $web;
                    
                    $arr = explode(".",$web);
                    switch($arr[count($arr)-1]){
                        case "sk":
                            $item["lang"] = "sk";
                        break;
                        case "cz":
                            $item["lang"] = "cs";
                        break;
                        case "default":
                            $item["lang"] = "en";
                        break;
                    }
                    

                    if(isset($done[$item["web"]])) continue;
                    $done[$item["web"]] = true;
                    if(!isset($allnewsByTime[$item["time"]])){
                        $allnewsByTime[$item["time"]] = [];
                    }
                    
                    $allnewsByTime[$item["time"]][] = $item;
                }
            }
        }
        krsort($allnewsByTime);
        return $allnewsByTime;
    }
    public function makeNewsPage($cty = "cz",$type="24h", $refresh = false, $maxWords = 150,$saveToCache = true,$processHistory=true){
        $lang = "cs";
        if($cty == "sk") $lang = $cty;
        if($cty == "en") $lang = $cty;
        
        $min = 100;
        switch($type){
            case "1h":
            $t = strtotime("-1 hours");
            $min = 20;
                break;
            case "3h":
            $t = strtotime("-3 hours");
            $min = 40;
                break;
            case "12h":
            $t = strtotime("-12 hours");
            $min = 50;
                break;
            case "w":
            $t = strtotime("-7 days");
            $min = 100;
                break;
            default:
            $t = strtotime("-24 hours");
            $min = 100;
                break;
        }
        
        
        $f = "/dev/shm/$cty-fin-news-$type.html";
        $page = "/Spravy/";
        if($cty == "sk") $page = "/Page:Spravy/";
        
        
        if(!$refresh){
            if(file_exists($f)){
                return ["msgs"=>json_decode(file_get_contents($f),true),"time"=>filemtime($f)];
                /*
                $mtime = filemtime($f);
                if($mtime > time() - 10*60){
                    return json_decode(file_get_contents($f),true);
                }/**/
            }
        }
        require_once("/cron/watchdogsk/ProcessHtml2Text.php");
        
        
        
        $res = \AsyncWeb\DB\DB::qb("dev02fast.${cty}_spravy_texts_clean",array(
            "order"=>array("od"=>"desc"),
            "where"=>[["col"=>"time","op"=>"gt","value"=>$t]],
        ));
        $count = DB::num_rows($res);
        if($count < $min){
            $res = \AsyncWeb\DB\DB::qb("dev02fast.${cty}_spravy_texts_clean",array(
                "limit"=>$min,
                "order"=>array("od"=>"desc")
            ));
            $count = DB::num_rows($res);
        }
        $ret = [];
        while($row=\AsyncWeb\DB\DB::f($res)){
             $result = \ProcessHtml2Text::ProcessWords($row["web"],$row["text"],$lang,true,true);
             foreach($result as $k=>$v){
                 if($v > 5){
                     $result[$k] = 5;
                 }
             }
             if($result["Lidovky"]){
                 //var_dump($row["headline"]);
                 //var_dump($row["web"]);
                 //exit;
             }
             foreach($result as $k=>$v){
                 if(isset($ret[$k])){
                     $ret[$k] += $v;
                 }else{
                     if(isset($ret[$k])){
                        $ret[$k] = $v + $ret[$k];
                     }else{
                         $ret[$k] = $v;
                     }
                 }
             }
             $result = \ProcessHtml2Text::ProcessWords($row["web"],$row["headline"],$lang,true,true);
             foreach($result as $k=>$v){
                 if(isset($ret[$k])){
                     $ret[$k] += $v;
                 }else{
                     if(isset($ret[$k])){
                        $ret[$k] = $v + $ret[$k];
                     }else{
                         $ret[$k] = $v;
                     }
                 }
             }
        }

        $old = [];
        if($processHistory){
            $res = \AsyncWeb\DB\DB::qb("dev02fast.${cty}_spravy_texts_clean",array("limit"=>round($count*1.5),"offset"=>$count,"order"=>array("od"=>"desc")));
            while($row=\AsyncWeb\DB\DB::f($res)){
                 $result = \ProcessHtml2Text::ProcessWords($row["web"],$row["text"],$lang,true,true);
                 foreach($result as $k=>$v){
                     if($v > 5){
                         $result[$k] = 5;
                     }
                 }
                 foreach($result as $k=>$v){
                     if(isset($old[$k])){
                         $old[$k] += $v;
                     }else{
                         $old[$k] = $v;
                     }
                 }
                 $result = \ProcessHtml2Text::ProcessWords($row["web"],$row["headline"],$lang,true,true);
                 foreach($result as $k=>$v){
                     if(isset($old[$k])){
                         $old[$k] += $v;
                     }else{
                         $old[$k] = $v;
                     }
                 }
            }
        }
        arsort($ret);
        $n = 0;
        
        $weights = [];
        
        //if(URLParser::v("debug") == "1"){
        $wc = DB::qbr("dev02fast.${lang}_spravy_texts_wordcount",["where"=>$w = ["type"=>"month","date"=>(date("Y-m",strtotime("-1 months")))],"cols"=>["clear"]]);
        $clear = gzuncompress($wc["clear"]);
        
        $clear = json_decode($clear,true);
        $max2 = reset($clear);
        
        foreach($ret as $k=>$v){$i++;
            $c = Texts::clear($k);
            if(!isset($clear[$c])){
                $weights[$c] = 0.5;
                continue;
            }
            
            $weights[$c] = ($max2 - $clear[$c]) / $max2;
        }
//        }
        
        $max = reset($ret);
        $i = 0;$c = count($ret);
        foreach($ret as $k=>$v){$i++;
//            if($i < 30) continue;
            $clear = Texts::clear($k);
            $weight = (10 * $weights[$clear] + 5*($max - $v + 30)/$max + 2*(($c - $i) / $c) + min(strlen($clear),7) / 7) /18;
            $sort[$k] = $weight;
        }
        if(URLParser::v("debug") == "1"){
            arsort($sort);
        }
/*
        $i = 0;$c = count($old);
        foreach($old as $k=>$v){$i++;
            if(isset($weights[$clear])){
                $weight = $weights[$clear];
            }else{
                $weight = 0.5;
            }
            $old[$k] = ( $weight*10+5*($max - $v + 30)/$max + 2*(($c - $i) / $c) + min(strlen($k),7) / 7) /18;
        }
        /**/
        arsort($sort);
        

        $sort2=[];
        $size = [];
        $s = 45;
        foreach($sort as $k=>$v){
            $n++;
            if($n > $maxWords) break;
            $sort2[$k] = $v;
            
            if($n%20==0) $s = round($s / 10 * 8.5);
            $size[$k] = $s;
        }
        ksort($sort2,SORT_STRING | SORT_FLAG_CASE);
        /*
        var_dump(reset($ret));
        var_dump($old);
        var_dump($ret);
        exit;
        /**/
         
        foreach($sort2 as $k=>$v){
            /*
          var_dump($ret[$k]);
          var_dump($old[$k]);
          exit;
          /**/
            if(!isset($old[$k]) || ($old[$k] * 2 <= $ret[$k])){
                $color = "#00".bin2hex(chr(255-round(($old[$k] * 2)/$ret[$k]*255/2)))."00";
            }else{
                $color = "#".bin2hex(chr(255-round($ret[$k]/($old[$k] * 2)*255/2)))."0000";
            }

            $ret2[] = [
            "k"=>$k,
            "page"=>$page,
            "size"=>$size[$k],
            "weight"=>$sort2[$k],
            "num"=>$ret[$k],
            "html"=>'<a href="'.$page.'search='.urlencode($k).'" style="font-size:'.$size[$k].'px; color:'.$color.'" >'.$k.'</a> '];
        }
        //ksort($ret2);
        //shuffle($ret2);
        //unlink($f);
        if($saveToCache){
            $result = file_put_contents($f,json_encode($ret2));
            if($refresh){
                echo " $f $result\n";
            }
        }   
        return ["msgs"=>$ret2,"time"=>filemtime($f)];
    }
 
    
}