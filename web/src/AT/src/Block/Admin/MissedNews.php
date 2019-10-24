<?php
namespace AT\Block\Admin;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class MissedNews extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"];
	public function init(){
        $lang = URLParser::v("lang");
        if(!$lang) $lang = "cz";
        $errcode = URLParser::v("errcode");
        if(!$errcode) $errcode = 8;
        
        
        if(URLParser::v("rebuild") && ($site = URLParser::v("site")) && ($t = strtotime(URLParser::v("time")))){
            if($t > 0){
                $site = DB::myAddSlashes($site);
                //var_dump($lang);exit;
                $res = DB::query("update `dev02`.`${lang}_spravy_texts` set spracovane = '1' where od > '$t' and web like '%$site%'");
                $c = DB::affected_rows();
                \AsyncWeb\Text\Msg::mes("Na spracovanie $c zaznamov");
                header("Location: https://www.cz-fin.com/Admin_MissedNews/lang=$lang");
                exit;
            }
        }
        
        $res = DB::qb("dev02.${lang}_spravy_texts",["cols"=>["time","id2","web"],"limit"=>30,"order"=>["time"=>"desc"],"where"=>[
            "spracovane"=>$errcode,
            "od"=>["col"=>"od","op"=>"gt","value"=>strtotime("-7 days")],
            ]]);
        $data = [];
        //var_dump(DB::error());
        while($row=DB::f($res)){
            $row["Time"] = date("d.m.Y H:i:s",$row["time"]);
            $data[] = $row;
        }
        $this->setData(["News"=>$data,"lang"=>$lang,"errcode"=>$errcode]);
	}
}