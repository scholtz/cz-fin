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
    public function getVS(){
        $vs = hexdec(substr(md5($this->email),0,6));
        if($vs < 1000000000) $vs+=1000000000;
        return $vs;
    }
    public function getSS(){
        return time();
    }
}