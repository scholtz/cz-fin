<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class MonitoringStats extends \AsyncWeb\Frontend\Block{
	public function init(){
        
        $tables = ["sk"=>"dev02fast.sk_spravy_texts_clean","cz"=>"dev02fast.cz_spravy_texts_clean","en"=>"dev02fast.en_spravy_texts_clean"];
        $ret = [];
        foreach($tables as $lang=>$table){
            $stats = [];
            $res = DB::qb($table,["cols"=>["web","text"],"where"=>["time"=>["col"=>"time","op"=>"gte","value"=>strtotime("-7 days")]]]);
            while($row=DB::f($res)){
                if(!$row["text"]) continue;
                $web = str_replace(["https://","http://"],"",$row["web"]);
                $doma = explode(".",$dom = strtok($web,"/"));
                
                if(URLParser::v("type") == "2"){
                    if(count($doma) == 2){
                        $c = count($doma);
                        @$stats[$dom]++;
                    }else{
                        $c = count($doma);
                        @$stats[$doma[$c-2].".".$doma[$c-1]]++;
                        //@$stats[$dom]++;
                    }
                }else{
                if(count($doma) == 2){
                    $c = count($doma);
                    @$stats[$dom]++;
                }elseif(count($doma) == 3){
                    $c = count($doma);
                    @$stats[$dom]++;
                }else{
                    $c = count($doma);
                    @$stats[$doma[$c-3].".".$doma[$c-2].".".$doma[$c-1]]++;
                    //@$stats[$dom]++;
                }
                }
            }
            
            arsort($stats);
            
            $retlang = [];
            foreach($stats as $k=>$v){
                $retlang[] = ["Domain"=>$k,"Count"=>$v];
            }
            $ret[] = ["Lang"=>$lang,"Sources"=>$retlang];
        }
        $this->setData(["Stats"=>$ret]);
	}
}