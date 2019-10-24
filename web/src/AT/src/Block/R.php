<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class R extends \AsyncWeb\Frontend\Block{
    // store referal code
    protected $requiresAuthenticatedUser = true;
	public function initTemplate(){
        if(URLParser::v("c")){
            if(strlen(URLParser::v("c") <= 10)){
                \AsyncWeb\Storage\Session::set("ReferalCode",URLParser::v("c"));
            }
        }
        header("Location: https://www.cz-fin.com/Buy/type=personal");
        exit;
	}
}