<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class TestEmail extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
	public function initTemplate(){
        
        var_dump(\AsyncWeb\Email\Email::send("ludkosk@gmail.com","Test cz-fin email","Toto je test: ".date("c")));
        exit;
        
	}
}