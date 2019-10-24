<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class NewBonus extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
	public function init(){
        $data = [];
        $code = "w".bin2hex(random_bytes(3));
        $exp = strtotime("+7 days");
        if(\AsyncWeb\Objects\User::getEmailOrId()){
            DB::u("fin_referal",md5(uniqid()),["type"=>"week","user"=>\AsyncWeb\Objects\User::getEmailOrId(),"code"=>$code,"expire"=>$exp]);
            $usr = DB::qbr("users",["cols"=>["firstname"],"where"=>["login"=>\AsyncWeb\Objects\User::getEmailOrId()]]);
        }
        $this->setData(["code"=>$code,"validity"=>date("d.m.Y H:i:s",$exp),"expire"=>$exp,"FirstName"=>$usr["firstname"]]);
	}
}