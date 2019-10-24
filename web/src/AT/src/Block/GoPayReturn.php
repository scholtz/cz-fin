<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class GoPayReturn extends \AsyncWeb\Frontend\Block{
    public function initTemplate(){
        $gopay = new \AT\Classes\GoPay();
        $ret = $gopay->validate(URLParser::v("id"));
        if($ret["error"]){
            \AsyncWeb\Text\Msg::err($ret["error"]);
        }
        if($ret["msg"]){
            \AsyncWeb\Text\Msg::mes($ret["msg"]);
        }
        if($ret["redirect"]){
            header("Location: ".$ret["redirect"]);
            exit;
        }
        exit;
	}
}