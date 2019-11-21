<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;
use AsyncWeb\System\Language;
use AsyncWeb\Text\Texts;

class MonitoringInfo extends \AsyncWeb\Frontend\Block{
	public function init(){
        
        $time = date("ym");
        $time1 = date("ym",strtotime("-1 month"));
        
        $tables = [
            "dev02fast.sk_spravy_texts_clean"=>"sk",
            "dev02fast.cz_spravy_texts_clean"=>"cz",
            "dev02fast.en_spravy_texts_clean"=>"en",
            
            "dev02fast.sk_${time}_news_texts"=>"sk",
            "dev02fast.cs_${time}_news_texts"=>"cz",
            "dev02fast.en_${time}_news_texts"=>"en",
            "dev02fast.ja_${time}_news_texts"=>"ja",
            "dev02fast.ru_${time}_news_texts"=>"ru",
            "dev02fast.de_${time}_news_texts"=>"de",
            
            "dev02fast.sk_${time1}_news_texts"=>"sk",
            "dev02fast.cs_${time1}_news_texts"=>"cz",
            "dev02fast.en_${time1}_news_texts"=>"en",
            "dev02fast.ja_${time1}_news_texts"=>"ja",
            "dev02fast.ru_${time1}_news_texts"=>"ru",
            "dev02fast.de_${time1}_news_texts"=>"de",
        ];
        
        $type = URLParser::v("type");
        if(!$type) $type = "2";
        
        $ret = [];
        $stats = [];
        $counter = 0;
        foreach($tables as $table=>$lang){
            $res = DB::qb($table,["cols"=>["web","text"],"where"=>["time"=>["col"=>"time","op"=>"gte","value"=>strtotime("-7 days")]]]);
            while($row=DB::f($res)){
                if(!$row["text"]) continue;
                $counter++;
                $web = str_replace(["https://","http://"],"",$row["web"]);
                $doma = explode(".",$dom = strtok($web,"/"));
                
                if($type == "2"){
                    if(count($doma) == 2){
                        $c = count($doma);
                        @$stats[$lang][$dom]++;
                    }else{
                        $c = count($doma);
                        @$stats[$lang][$doma[$c-2].".".$doma[$c-1]]++;
                        //@$stats[$dom]++;
                    }
                }else{
                if(count($doma) == 2){
                    $c = count($doma);
                    @$stats[$lang][$dom]++;
                }elseif(count($doma) == 3){
                    $c = count($doma);
                    @$stats[$lang][$dom]++;
                }else{
                    $c = count($doma);
                    @$stats[$lang][$doma[$c-3].".".$doma[$c-2].".".$doma[$c-1]]++;
                    //@$stats[$dom]++;
                }
                }
            }
            
        }
        foreach($stats as $lang=>$arr){
            arsort($arr);
            
            $retlang = [];
            $out = "";
            foreach($arr as $k=>$v){
                $retlang[] = ["Domain"=>$k,"Count"=>$v];
                if($out) $out .=", ";
                $out.=$k;
            }
            $ret[] = ["LangName"=>Language::get("Language-$lang"),"Lang"=>$lang,"Sources"=>$retlang,"List"=>$out];

        }
             

        $out = ["Stats"=>$ret,"PromoText"=>Language::get("We have processed %count% articles in last 7 days. Data sources below are sorted by the number of processed articles.",["%count%"=>number_format($counter,0,","," ")])];
        
        $this->setData($out);
	}
}