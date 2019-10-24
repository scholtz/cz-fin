<?php
namespace AT\Block;

use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;

use AsyncWeb\Text\Texts;

class Ticket extends \AsyncWeb\Frontend\Block{
    protected $requiresAuthenticatedUser = true;
	public function init(){
        
        $this->setData(["code"=>$row["code"],"sent"=>URLParser::v("sent") || URLParser::v("sent")]);
	}
}