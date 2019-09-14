<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Search extends \AsyncWeb\Frontend\Block{
	
	public function init(){
        $current = URLParser::v("text");
        
        if(strlen($current) == 8 && $row = DB::gr("data_czfin_pages",["id2"=>$current])){
            header("Location: https://cz-fin.com/Content_Cat:Firma/ico=$current/");
            exit;
        }
        
        $clear = Texts::clear($current);
        if($clear){
            $res = DB::qb("data_czfin_rating",["where"=>[["col"=>"clear","op"=>"like","value"=>"%$clear%"]],"limit"=>50,"order"=>["rating"=>"desc"]]);
            $firmy = [];
            while($row=DB::f($res)){
                $firmy[] = ["Name"=>$row["obchodnifirma"] ?? $row["clear"] ?? "?","ICO"=>$row["id2"],"clear"=>$row["clear"],"Rating"=>number_format(100*$row["rating"]/$row["ratingmax"],2,",","&nbsp;")];
            }
            $this->setData(["Term"=>$current,"Firmy"=>$firmy]);
        }
	}
	
}