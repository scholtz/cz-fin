<?php
namespace AT\Classes;

use AsyncWeb\System\Language;
use AsyncWeb\Text\Texts;
use AsyncWeb\DB\DB;
use AsyncWeb\Frontend\URLParser;

class Payment{
    private $email;
    public function __construct($email = null){
        if(!$email){
            $this->email = \AsyncWeb\Objects\User::getEmailOrId();
        }else{
            $this->email = $email;
        }
    }
    public function getVS($currency = "czk"){
        return $this->getNextNumber($currency);
    }
    public function getSS(){
        $vs = hexdec(substr(md5($this->email),0,6));
        if($vs < 1000000000) $vs+=1000000000;
        return $vs;
    }
    
    public function getNextNumber($currency){
        $code = "";
        switch(strtolower($currency)){
            case "czk":
                $code = "22";
            break;
            case "eur":
                $code = "23";
            break;
            case "usd":
                $code = "24";
            break;
            default: 
                $code = "25";
        }
        $y = date("y");
        $row = DB::qbr("fin_orders",["cols"=>["c"=>"max(vs)"],"where"=>["currency"=>$currency]]);
        if($row["c"]){
           $latest = $row["c"];
        
        }else{
           $latest = $y.$code."000381";
        }
        $latestnumber = substr($latest,-6);
        return $y.$code.str_pad($latestnumber+1,6,"0",STR_PAD_LEFT);
    }
}