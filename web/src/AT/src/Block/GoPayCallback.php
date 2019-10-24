<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class GoPayCallback extends \AsyncWeb\Frontend\Block{
    public function initTemplate(){
        
        
        $gopay = new \AT\Classes\GoPay();
        
        $ret = $gopay->validate(URLParser::v("id"));
        if($ret["error"]){
            \AsyncWeb\Text\Msg::err($ret["error"]);
        }
        if($ret["msg"]){
            \AsyncWeb\Text\Msg::mes($ret["msg"]);
        }
        
        header("Content-Type: application/json");
        echo json_encode($ret); 
        exit;
	}
}