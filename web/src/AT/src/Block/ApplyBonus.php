<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class ApplyBonus extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
	public function init(){
        
        if(URLParser::v("couponCode")){
            
            $coderow = DB::gr("fin_referal",["code"=>$code = URLParser::v("couponCode")]);
            //var_dump($coderow);exit;
            if(!$coderow["used"] && $coderow["type"] == "full"){
                
                \AT\Classes\Licence::newLicenceByCoupon(URLParser::v("couponCode"));
                header("Location: https://".$_SERVER["HTTP_HOST"]."/Form_AdminLicenceOverview");
                exit;
            }
            
            if(strlen(URLParser::get("couponCode") <= 10)){
                \AsyncWeb\Storage\Session::set("ReferalCode",URLParser::v("couponCode"));
            }
            header("Location: https://".$_SERVER["HTTP_HOST"]."/Buy/type=".URLParser::v("type"));
            exit;
        }
        $this->setData(["type"=>URLParser::v("type"),"code"=>\AsyncWeb\Storage\Session::get("ReferalCode")]);
	}
}