<?php
namespace AT\Block\Admin;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class News extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
    protected $requiresAllGroups = ["admin"=>"admin"];
	public function init(){
        $lang = URLParser::v("lang");
        if($lang == "cs") $lang = "cz";
        if(!$lang) $lang = "cz";
        $errcode = URLParser::v("errcode");
        if(!$errcode) $errcode = 8;
        
        $row = DB::qbr("dev02.${lang}_spravy_texts",["cols"=>["time","id2","web","data"],"where"=>["id2"=>URLParser::v("id")]]);
        if(!$row){
            $this->setData(["Error"=>"Not found"]);
            return;
        }
        $text = gzuncompress($row["data"]);
        if(!$text){
            $this->setData(["Error"=>"GZIP compression error"]);
            return;
        }
        
        require_once("/cron/watchdogsk/ProcessHtml2Text.php");
        $data = \ProcessHtml2Text::Process($row["web"],$text);
        if(!$text){
            $this->setData(["Error"=>"No data processed"]);
            return;
        }
        if(!$data) $data = [];
        $data["Time"] = date("d.m.Y H:i:s",$row["time"]);
        $data["lang"] = $lang;
        $data["errcode"] = $errcode;
        $data["web"] = $row["web"];
        $this->setData($data);
	}
}