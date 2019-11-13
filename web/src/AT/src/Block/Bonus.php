<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;
use AsyncWeb\System\Language;

use AsyncWeb\Text\Texts;

class Bonus extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
	public function init(){
        
        if(URLParser::v("send") && URLParser::v("text") && \AsyncWeb\Text\Validate::check("email",URLParser::v("email"))){
            $usr = DB::qbr("users",["cols"=>["firstname"],"where"=>["login"=>\AsyncWeb\Objects\User::getEmailOrId()]]);
            $name = trim($usr["firstname"]." ".$usr["lastname"]);
            $title = Language::get("%name% Vám poslal slevový kód",["%name%"=>$name]);

            $m = new \Mustache_Engine();
            $data = [];
            $data["Name"] = $name;
            $data["Message"] = URLParser::v("text");
            $html = \AsyncWeb\Text\Template::loadTemplate("Email_Referal",$data);
            
            \AsyncWeb\Email\Email::send(URLParser::v("email"),$title,$html,"info@cz-fin.com",[],"html");
            
            header("Location: https://".$_SERVER["HTTP_HOST"]."/Bonus/sent=1");
        }
        
        $row = DB::gr("fin_referal",["type"=>"prenament","user"=>\AsyncWeb\Objects\User::getEmailOrId()]);
        if(!$row){
            DB::u("fin_referal",md5(uniqid()),["type"=>"prenament","user"=>\AsyncWeb\Objects\User::getEmailOrId(),"code"=>bin2hex(random_bytes(4))]);
            $row = DB::gr("fin_referal",["type"=>"prenament","user"=>\AsyncWeb\Objects\User::getEmailOrId()]);
        }
        $this->setData(["code"=>$row["code"],"sent"=>URLParser::v("sent") || URLParser::v("sent")]);
	}
}